// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "cdt.h"
#include "ts_ctl.h"
#include "clt2png.h"

int parseCDThead(unsigned char *data, CDThead *h) {
	int boff = 0;

	memset(h, 0, sizeof(CDThead));

	h->table_id = getBit(data, &boff, 8);
	h->section_syntax_indicator = getBit(data, &boff, 1);
	h->reserved_future_use1 = getBit(data, &boff, 1);
	h->reserved1 = getBit(data, &boff, 2);
	h->section_length = getBit(data, &boff, 12);
	h->download_data_id = getBit(data, &boff, 16);
	h->reserved2 = getBit(data, &boff, 2);
	h->version_number = getBit(data, &boff, 5);
	h->current_next_indicator = getBit(data, &boff, 1);
	h->section_number = getBit(data, &boff, 8);
	h->last_section_number = getBit(data, &boff, 8);
	h->original_network_id = getBit(data, &boff, 16);
	h->data_type = getBit(data, &boff, 8);
	h->reserved_future_use2 = getBit(data, &boff, 4);
	h->descriptors_loop_length = getBit(data, &boff, 12);

	return 13;
}

int parseCDTdesc(unsigned char *data, CDTdesc *desc) {
// ほとんど呼ばれることはない
	int boff = 0;

	memset(desc, 0, sizeof(CDTdesc));

	desc->descriptor_tag = getBit(data, &boff, 8);
	desc->descriptor_length = getBit(data, &boff, 8);

	return desc->descriptor_length + 2;
}

int parseCDTdata(unsigned char *data, CDTdata *cdtd) {
	int boff = 0;

	memset(cdtd, 0, sizeof(CDTdata));

	cdtd->logo_type = getBit(data, &boff, 8);
	cdtd->reserved_future_use1 = getBit(data, &boff, 7);
	cdtd->logo_id = getBit(data, &boff, 9);
	cdtd->reserved_future_use2 = getBit(data, &boff, 4);
	cdtd->logo_version = getBit(data, &boff, 12);
	cdtd->data_size = getBit(data, &boff, 16);
	cdtd->data = calloc(1, cdtd->data_size);
	memcpy(cdtd->data, data + boff / 8, cdtd->data_size);
	//boff += cdtd->data_size * 8;

	return cdtd->data_size + 7;
}

void dumpCDT( unsigned char *ptr, SVT_CONTROL *svttop )
{
	CDThead  cdth;
	CDTdesc  desc;
	CDTdata  cdtd;
	LOGO * pLogo;
	SVT_CONTROL	*svtptr;

	int len = 0;
	int loop_len = 0;
	int desc_len = 0;

	/* CDT */
	len = parseCDThead(ptr, &cdth);
	ptr += len;
	loop_len = cdth.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC

	desc_len = cdth.descriptors_loop_length;
	while(desc_len > 0) {
		len = parseCDTdesc(ptr, &desc);
		ptr += len;
		desc_len -= len;
	}

	while(loop_len > 0) {
		/*
		logo_type
		0x00 24x48 864  SD4:3 スモール 
		0x01 24x36 648  SD16:9 スモール
		0x02 27x48 972  HD スモール 
		0x03 36x72 1296 SD4:3 ラージ 
		0x04 36x54 972  SD16:9 ラージ 
		0x05 36x64 1152 HD ラージ 
		*/
		len = parseCDTdata(ptr, &cdtd);
		ptr += len;
		loop_len -= len;
/*
		char fname[16];
		sprintf(fname,"%d_%d.png",cdth.download_data_id,cdtd.logo_type);
		FILE* png = fopen(fname,"wb");
		void* mem_png = NULL;
		int size_png;
		clt2png(cdtd.data,&mem_png, &size_png);
		fwrite(mem_png, 1, size_png, png);
		fclose(png);
*/
#if 0
		printf("CDT=(%d:%d:%d:%d:%d:%dbyte:desc%dbyte)%d,%d,%d,%d\n",
			cdth.table_id, cdth.download_data_id, cdth.version_number, 
			cdth.original_network_id, cdth.data_type, 
			cdth.section_length, cdth.descriptors_loop_length, 

			cdtd.logo_type, cdtd.logo_id, cdtd.logo_version,
			cdtd.data_size);
#endif
		svtptr = svttop;
		while( svtptr->next != NULL ){
			svtptr = svtptr->next;
			pLogo  = &svtptr->logo_array[cdtd.logo_type];
			if ( svtptr->logo_download_data_id == cdth.download_data_id ) {
				pLogo->logo = NULL;
				clt2png(cdtd.data, &pLogo->logo, &pLogo->logo_size);
			}
		}
	}
	return;
}

