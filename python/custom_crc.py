# -*- coding: utf-8 -*-
"""
Created on Fri Jun 17 13:29:29 2016

@author: kchew
"""

width = 16
poly = 0x1021
initalVal = 0xffff
reflectIn = True
reflectOut = True
xorOut = 0xffff


def getCastMask () :
    if (width == 8) :
        return 0xff
    elif (width == 16) :
        return 0xffff
    elif (width == 32) :
        return 0xffffffff

castMask = getCastMask()

msbMask = 0x01 << (width - 1)

def generateCrcTable ():
    crcTab = []
    
    for divident in range(0, 256) :
        currByte = (divident << (width - 8)) & castMask
        for bit in range(0, 8):                       
            if( (currByte & msbMask) != 0 ) : 
                currByte <<= 1 
                currByte ^= poly
            else :
                currByte <<= 1 
        crcTab.append(currByte & castMask)
            
    return crcTab

def reflect (val, refWidth) :
    refVal = 0
    
    for i in range(0, refWidth) :
        if( (val&(1 << i)) !=0 ) :
            refVal |= (1 << ((refWidth - 1) - i))
    
    return refVal

def calcCrc (data) :
    crc = initalVal
    
    for i in range(0, len(data)) :
        
        currByte = data[i] & 0xff        
        if (reflectIn) :    
            currByte = reflect(currByte,8)
        
        crc ^= ( currByte << (width - 8) )
        crc = ( (crc << 8) ^ crcTable[( (crc >> (width - 8) ) & 0xff)] ) 
    
    if (reflectOut) :
        crc = reflect(crc, width)
    
    return (crc ^ xorOut) & castMask

def calcHanChecksum (data) :
    crc = calcCrc(data)
    return (crc << 8) & 0xff00 | (crc & 0xff00) >> 8



instcode = 'C661 0FE4 8ED7'
data = bytes.fromhex(instcode)
# data = str('123456789').encode('utf-8')

crcTable = generateCrcTable()
print(hex(calcCrc(data)))
print(hex(calcHanChecksum(data)))