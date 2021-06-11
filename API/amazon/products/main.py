# coding=utf-8

import json
import sys
import pandas as pd

def getValidValues(row):
    validValues = []
    for item in row[3:]:
        if type(item) == str:
            validValues.append(proccessValues(item))
        else:
            break
    return validValues


def updateMetadata(globalMetadata, metadata,timeChanges):
    if((globalMetadata is None and type(metadata) == str) or (type(metadata) == str and  globalMetadata!= metadata and 'Unnamed' not in metadata) ):
        timeChanges +=1
        return [metadata,timeChanges]
    else:
        return [globalMetadata, timeChanges]

def proccessValues(value):

    if value == 'nan':
        return ''
    else:
        #return value.replace(u'\xa0', u' ')

        return value.replace(u'\xa0', u' ').strip()



def keyValueTypes(dataframe):
    map = {}
    mapRepeat = {}

    for row in dataframe.itertuples():
        value = row[1]

        if type(value) != str:
            key = proccessValues(str(row[2]))
            value = proccessValues(str(row[3]))

            description = proccessValues(str(row[4]).strip())
            example = proccessValues(str(row[5]).strip())
            if value not in map:
                map[value] = {
                    'value':proccessValues(key),
                    'description':proccessValues(description),
                    'example': proccessValues(example)
                }
            else:
                mapRepeat[value] = {
                    'value': proccessValues(key),
                    'description': proccessValues(description),
                    'example': proccessValues(example)
                }

    return map,mapRepeat

def globalProperties(keyProperties,df):

    colsName = []
    for col in df.columns:
        colsName.append(col)

    required = True
    iteration = 0
    metadata = None

    index = 0

    mapGlobal = {}

    tempExample = None
    tempDescription = None

    row = df.iloc[1]
    for item in row:
        if type(item) == str:
            if 'image' in item and 'main' not in item and iteration == 0:
                iteration+=1
                required = False
                metadata = colsName[index]
            elif iteration > 0: # actalizamos el metadata de forma normal
                metadata,iteration = updateMetadata(metadata,colsName[index],iteration)
                required = False

            trans = proccessValues(df.iloc[0][index])

            if trans in keyProperties:

                if iteration != 1:
                    example = keyProperties[str(trans)]['example']
                    description = keyProperties[str(trans)]['description']
                else:
                    if tempExample == None:
                        tempExample = keyProperties[str(trans)]['example']
                        tempDescription = keyProperties[str(trans)]['description']
                        example = tempExample
                        tempDescription = tempDescription
                    else:
                        example = tempExample
                        tempDescription = tempDescription

                example = proccessValues(example)
                description = proccessValues(description)

                if required == True:

                    if item not in mapGlobal:
                        mapGlobal[item] = {}

                    mapGlobal[item]['trans'] = trans
                    mapGlobal[item]['required'] = required
                    mapGlobal[item]['example'] = example
                    mapGlobal[item]['description'] = description

                else:
                    if metadata not in mapGlobal:
                        mapGlobal[metadata] = {}

                    if item not in mapGlobal[metadata]:
                        mapGlobal[metadata][item] = {}

                    mapGlobal[metadata][item]['trans'] = trans
                    mapGlobal[metadata][item]['required'] = required
                    mapGlobal[metadata][item]['example'] = example
                    mapGlobal[metadata][item]['description'] = description
            elif 'image' in item:


                if item not in  mapGlobal[metadata]:
                    mapGlobal[metadata][item] = {}

                mapGlobal[metadata][item]['trans'] = trans
                mapGlobal[metadata][item]['description'] = description
                mapGlobal[metadata][item]['required'] = required
                mapGlobal[metadata][item]['example'] = tempExample


        else:
            break
        index+=1

    return mapGlobal

def isBasicFile(path):
    if 'Flat.File' in path:
        return False
    else:
        return True

def splitName(path,df):

    title = ''
    if 'Flat.File' in path:
        title = path.split('.')[-3]
    else:
        title = path.split('/')[-1].split('.')[-2]

    colsName = []
    for col in df.columns:
        colsName.append(col)

    if 'settings' in colsName[3]:
        index = 3
    else:
        index = 4

    lang = colsName[index].split('&')[0].split('=')[-1]

    return [title,lang]


def main(path):

    #globalCategory = path.split('.')[-3]
    #lang = path.split('.')[-2]

    map = {}

    #name = "Flat.File.Clothing.es.xlsm"
    #name = "Flat.File.Health.es.xlsm"
    excel = pd.ExcelFile(path)
    #print(excel.sheet_names)
    df1 = excel.parse(excel.sheet_names[10])

    dfTemplate = excel.parse(excel.sheet_names[11])

    globalCategory,lang = splitName(path,dfTemplate)

    keysMapProperties, keysMapPropertiesRepeat = keyValueTypes(df1)
    globalData = globalProperties(keysMapProperties,dfTemplate)

    if isBasicFile(path) == True:
        df2 = excel.parse(excel.sheet_names[13])
    else:
        df2 = excel.parse(excel.sheet_names[12])

    map[globalCategory] = {'title': str(globalCategory),'lang':lang}
    map[globalCategory]['global'] = globalData

    rows = len(df2)
    cols = len(df2.columns)

    globalMetadata = 'required'
    timesChanged = 0

    for row in df2.itertuples():

        metadata = row[1]

        globalMetadata,timesChanged = updateMetadata(globalMetadata,metadata,timesChanged)
        if globalMetadata == 'required':
            required = True
        else:
            required = False

        values = getValidValues(row)

        if type(row[2]) == str:
            typeProductProperty = row[2].split('-')

            if row[2] in keysMapProperties:
                property = row[2]
            else:
                property = proccessValues(typeProductProperty[0].strip())

            if property not in keysMapProperties:
                continue

            if str(property) in keysMapProperties:
                propertyId = proccessValues(keysMapProperties[str(property)]['value'])
                example = proccessValues(keysMapProperties[str(property)]['example'])
                description = proccessValues(keysMapProperties[str(property)]['description'])
                del keysMapProperties[str(property)]

            elif str(property) in keysMapPropertiesRepeat:

                propertyId = proccessValues(keysMapPropertiesRepeat[str(property)]['value'])
                example = proccessValues(keysMapPropertiesRepeat[str(property)]['example'])
                description = proccessValues(keysMapPropertiesRepeat[str(property)]['description'])

            value = property

            if len(typeProductProperty)>1: # tenemos propiedad y categoria

                typeProduct = typeProductProperty[1].replace('[', ' ').replace(']', '').strip()

                if str(typeProduct) not in map[globalCategory] != False:
                    map[globalCategory][str(typeProduct)] = {}

                if globalMetadata not in map[globalCategory][str(typeProduct)]:
                    if required == False:
                        map[globalCategory][str(typeProduct)][globalMetadata] = {}

                if required == False and propertyId not in map[globalCategory][str(typeProduct)][globalMetadata]:
                    map[globalCategory][str(typeProduct)][globalMetadata][propertyId] = {}
                else:
                    map[globalCategory][str(typeProduct)][propertyId] = {}

            else:
                if globalMetadata not in map[globalCategory]['global'] and required == False:
                         map[globalCategory]['global'][globalMetadata] = {}

                if required == False:
                    if  propertyId not in map[globalCategory]['global'][globalMetadata]:
                        map[globalCategory]['global'][globalMetadata][propertyId] = {}

                elif propertyId not in map[globalCategory]['global']:
                    map[globalCategory]['global'][propertyId] = {}

            if len(typeProductProperty)>1:

                if required == False:
                    map[globalCategory][str(typeProduct)][globalMetadata][propertyId]['trans'] = value
                    map[globalCategory][str(typeProduct)][globalMetadata][propertyId]['required'] = required
                    map[globalCategory][str(typeProduct)][globalMetadata][propertyId]['values'] = values
                    map[globalCategory][str(typeProduct)][globalMetadata][propertyId]['example'] = example
                    map[globalCategory][str(typeProduct)][globalMetadata][propertyId]['description'] = description
                else:
                    map[globalCategory][str(typeProduct)][propertyId]['trans'] = value
                    map[globalCategory][str(typeProduct)][propertyId]['required'] = required
                    map[globalCategory][str(typeProduct)][propertyId]['values'] = values
                    map[globalCategory][str(typeProduct)][propertyId]['example'] = example
                    map[globalCategory][str(typeProduct)][propertyId]['description'] = description

            else:
                if required == False:

                    map[globalCategory]['global'][globalMetadata][propertyId]['trans'] = value
                    map[globalCategory]['global'][globalMetadata][propertyId]['required'] = required
                    map[globalCategory]['global'][globalMetadata][propertyId]['values'] = values
                    map[globalCategory]['global'][globalMetadata][propertyId]['example'] = example
                    map[globalCategory]['global'][globalMetadata][propertyId]['description'] = description
                else:
                    map[globalCategory]['global'][propertyId]['trans'] = value
                    map[globalCategory]['global'][propertyId]['required'] = required
                    map[globalCategory]['global'][propertyId]['values'] = values
                    map[globalCategory]['global'][propertyId]['example'] = example
                    map[globalCategory]['global'][propertyId]['description'] = description

            if required == False:
                if propertyId in map[globalCategory]['global'][globalMetadata]:
                    if 'values' not in  map[globalCategory]['global'][globalMetadata][propertyId] or len(values) > len (map[globalCategory]['global'][globalMetadata][propertyId]['values']):
                        map[globalCategory]['global'][globalMetadata][propertyId]['values'] = values

            elif propertyId in map[globalCategory]['global']:
                map[globalCategory]['global'][propertyId]['values'] = values



    response = json.dumps(map, sort_keys=False, indent=4)
    print(response)
    return response

# Press the green button in the gutter to run the script.
if __name__ == '__main__':

    if len(sys.argv) >= 2 :
        main(sys.argv[1])
        exit()
    exit(0)




# See PyCharm help at https://www.jetbrains.com/help/pycharm/
