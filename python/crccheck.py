# -*- coding: utf-8 -*-
"""
Created on Thu Jun 16 10:12:33 2016

@author: kchew
"""

from crccheck.crc import CrcX25
from crccheck.crc import Crc

# instcode = 'C661 0FE4 8ED7'
# instcode = '83FE D340 7A93 9723 A5C6 39FF'


# Install Code of C661 0FE4 8ED7 262E, where the checksum is 262E
#

a = Crc(16, 0x1021, 0xffff, True, True, 0xffff)

# data = bytes.fromhex(instcode)
data = str('123456789').encode('utf-8')

#print(CrcX25.calchex(bytes("123456789",'utf-8')))

# crcout = CrcX25.calc(data)

a.process(data)
crcout = a.finalhex()

print(crcout)

instcrc = ( (a.final() & 0xff) << 8 ) | ( a.final() >> 8 )

print(hex(instcrc))
