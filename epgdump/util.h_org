#ifndef UTIL_H
#define UTIL_H 1

#include <time.h>

#define MAXSECLEN 4096			//8192

#define BCD(n) (((n)>>4)*10+((n)&0x0FU))
#define WBCD(n) ((((n)&0xF000U)>>12)*1000+(((n)&0x0F00U)>>8)*100+(((n)&0x00F0U)>>4)*10+((n)&0x000FU))

#define TRUE  (1)
#define FALSE (0)

#define	ON	(1)
#define	OFF	(0)

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	int strrep(char *buf, char *mae, char *ato);
	unsigned int    getBit(unsigned char *byte, int *pbit, int gbit);
	void   getStr(char *tostr, unsigned char *byte, int *pbit, int len);
	int    parseOTHERdesc(unsigned char *data);
	void*  allocCopy(void* src, int *boff, size_t size);
	time_t parseMJD( unsigned char *data );
	int    chkBCD60( unsigned char src );
	int    chkBCD( unsigned char src );

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif
