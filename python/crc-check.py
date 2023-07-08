import crcmod

xmodem_crc_func = crcmod.mkCrcFun(0x11021, rev=True, initCrc=0x0000, xorOut=0xFFFF)

# Install Code of C661 0FE4 8ED7 262E, where the checksum is 262E
#

bytes = bytearray.fromhex('C661 0FE4 8ED7')
out = hex(xmodem_crc_func(bytes))


print(out)
