// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "tot.h"
#include "ts_ctl.h"

int parseTOThead(unsigned char *data, TOThead *toth) {
	int boff = 0;

	memset(toth, 0, sizeof(TOThead));

	toth->table_id = getBit(data, &boff, 8);
	toth->section_syntax_indicator = getBit(data, &boff, 1);
	toth->reserved_future_use1 = getBit(data, &boff, 1);
	toth->reserved1 = getBit(data, &boff, 2);
	toth->section_length = getBit(data, &boff, 12);
	memcpy(toth->JST_time, data + boff / 8, 5);
	boff += 40;
	// toth->JST_time = getBit(data, &boff, 40);
	toth->reserved2 = getBit(data, &boff, 4);
	toth->descriptors_loop_length = getBit(data, &boff, 12);

	return 10;
}

int parseTOTdesc(unsigned char *data, TOTdesc *totd) {
	int boff = 0;

	memset(totd, 0, sizeof(TOTdesc));

	totd->descriptor_tag = getBit(data, &boff, 8);
	totd->descriptor_length = getBit(data, &boff, 8);

	return totd->descriptor_length + 2;
}

void dumpTOT(unsigned char *ptr)
{
	TOThead  toth;
//	TOTdesc  totd;

	int len = 0;
	int loop_len = 0;
//	int desc_len = 0;
	time_t tot;

	/* TOT */
	len = parseTOThead(ptr, &toth);
	ptr += len;
	loop_len = toth.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC

	tot = parseMJD( toth.JST_time );
	printf("TOT diff:%d[秒] %s",
		(int)difftime( time(NULL), tot ), ctime(&tot));
	//ptm = localtime(time(NULL));
/*
	desc_len = toth.descriptors_loop_length;
	while(desc_len > 0) {
		len = parseTOTdesc(ptr, &totd);
		ptr += len;
		desc_len -= len;
	}
*/
	return;
}

