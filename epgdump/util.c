#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "util.h"
#include "aribstr.h"
#include "ts_ctl.h"

int strrep(char *buf, char *mae, char *ato)
{
    char *mitsuke, *findpos;
	size_t maelen, atolen;
	int shift;
		    
	findpos = buf;
	maelen = strlen(mae);
	atolen = strlen(ato);
	shift = (int)(strlen(ato)-strlen(mae));

	if (maelen == 0 || strstr(findpos, mae) == NULL) return 0;
	while ((mitsuke = strstr(findpos, mae)) != NULL) {
		if (shift > 0) {
			memmove(mitsuke + shift, mitsuke, strlen(mitsuke) + 1);
		} else if (shift < 0) {
			memmove(mitsuke, mitsuke - shift, strlen(mitsuke) + shift + 1);
		}
		memmove(mitsuke, ato, atolen);
		findpos = mitsuke + atolen;
	}
	return 1;
}

unsigned int getBit(unsigned char *byte, int *pbit, int gbit) {
	int pbyte = *pbit / 8;
	unsigned char *fbyte = byte + pbyte;

	int cutbit = *pbit - (pbyte * 8);
	int lcutbit = 32 - (cutbit + gbit);

	unsigned char tbuf[4]; /* intの最大32bit */
	unsigned int tnum;

	memcpy(tbuf, fbyte, sizeof(unsigned char) * 4);

	/* 先頭バイトから不要bitをカット */
	tbuf[0] = tbuf[0] << cutbit;
	tbuf[0] = tbuf[0] >> cutbit;

	/* intにしてしまう */
	tnum = (unsigned int)tbuf[0] << 24 | (unsigned int)tbuf[1] << 16 | (unsigned int)tbuf[2] << 8 | (unsigned int)tbuf[3];

	/* 後ろの不要バイトをカット */
	tnum = tnum >> lcutbit;

	*pbit += gbit;

	return tnum;
  
}

void getStr(char *tostr, unsigned char *byte, int *pbit, int len) {
	char str[MAXSECLEN];
	int pbyte = *pbit / 8;
	unsigned char *fbyte = byte + pbyte;

//	memset(str, 0, sizeof(char) * MAXSECLEN);
	memcpy(str, fbyte, len);
	str[len] = '\0';

	*pbit += (len * 8);
  
	AribToString(tostr, (const unsigned char *)str, len);

	return;
  
}

int parseOTHERdesc(unsigned char *data) {
	int boff = 0;
	int descriptor_tag;
	int descriptor_length;

	descriptor_tag = getBit(data, &boff, 8);
	descriptor_length = getBit(data, &boff, 8);

	/* printf("other desc_tag:0x%x\n", descriptor_tag); */

	return descriptor_length + 2;
}

void* allocCopy(void* src, int *boff, size_t size) {
	void* mem = NULL;
	if ( size == 0 ) return NULL;

	mem = malloc(size);
	memcpy(mem, src + *boff / 8, size);
	*boff += size * 8;
	return mem;
}


int chkBCD60( unsigned char src ){
	if( (src>>4) >= 6 || (src & 0x0FU) > 9 )
		return FALSE;
	return TRUE;
}


int chkBCD( unsigned char src ){
	if( (src>>4) > 9 || (src & 0x0FU) > 9 )
		return FALSE;
	return TRUE;
}


time_t parseMJD( unsigned char *data ) {
	int tnum, year, mon, mday;
	struct tm MJD;

	tnum = (data[0] & 0xFF) << 8 | (data[1] & 0xFF);
	year = (tnum - 15078.2) / 365.25;
	mon  = ((tnum - 14956.1) - (int)(year * 365.25)) / 30.6001;
	mday = (tnum - 14956) - (int)(year * 365.25) - (int)(mon * 30.6001);

	if(mon == 14 || mon == 15) {
		year += 1;
		mon  -= 13;
	} else {
		mon -= 1;
	}

	memset( &MJD, 0, sizeof(MJD) );
	MJD.tm_year = year;
	MJD.tm_mon  = mon - 1;
	MJD.tm_mday = mday;

	MJD.tm_hour = BCD(data[2]);
	MJD.tm_min  = BCD(data[3]);
	MJD.tm_sec  = BCD(data[4]);
	return mktime( &MJD );
}


time_t timeParse( EIT_CONTROL *cur )
{
	struct tm	tl;

	tl.tm_year  = cur->yy;
	tl.tm_mon   = cur->mm - 1;
	tl.tm_mday  = cur->dd;
	tl.tm_hour  = cur->hh;
	tl.tm_min   = cur->hm;
	tl.tm_sec   = cur->ss;
	tl.tm_wday  = 0;
	tl.tm_isdst = 0;
	tl.tm_yday  = 0;
	return mktime( &tl );
}


void dateParse( EIT_CONTROL *dts, time_t *src )
{
	struct tm	*tl;

	tl = localtime( src );
	dts->yy = tl->tm_year;
	dts->mm = tl->tm_mon + 1;
	dts->dd = tl->tm_mday;
	dts->hh = tl->tm_hour;
	dts->hm = tl->tm_min;
	dts->ss = tl->tm_sec;
}
