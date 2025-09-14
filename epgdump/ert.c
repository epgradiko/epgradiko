#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#include "util.h"
#include "ert.h"
#include "ts_ctl.h"


int parseERThead(unsigned char *data, ERThead *h) {
	int boff = 0;

	memset(h, 0, sizeof(ERThead));

	h->table_id                 = getBit(data, &boff, 8);
	h->section_syntax_indicator = getBit(data, &boff, 1);
	h->reserved_future_use1     = getBit(data, &boff, 1);
	h->reserved1                = getBit(data, &boff, 2);
	h->section_length           = getBit(data, &boff,12);
	h->event_relation_id        = getBit(data, &boff,16);
	h->reserved2                = getBit(data, &boff, 2);
	h->version_number           = getBit(data, &boff, 5);
	h->current_next_indicator   = getBit(data, &boff, 1);
	h->section_number           = getBit(data, &boff, 8);
	h->last_section_number      = getBit(data, &boff, 8);
	h->information_provider_id  = getBit(data, &boff,16);
	h->relation_type            = getBit(data, &boff, 4);
	h->reserved_future_use2     = getBit(data, &boff, 4);
  
	return 11;
}

int parseERTbody(unsigned char *data, ERTbody *b)
{
	int				boff = 0;

	memset(b, 0, sizeof(ERTbody));

	b->node_id                 = getBit(data, &boff,16);
	b->collection_mode         = getBit(data, &boff, 4);
	b->reserved_future_use1    = getBit(data, &boff, 4);
	b->parent_node_id          = getBit(data, &boff,16);
	b->reference_number        = getBit(data, &boff, 8);
	b->reserved_future_use2    = getBit(data, &boff, 4);
	b->descriptors_loop_length = getBit(data, &boff,12);

	return 8;
}


void dumpERT(unsigned char *ptr, SVT_CONTROL *svttop )
{

	ERThead  erth;
	ERTbody  ertb;

	SVT_CONTROL	*svtcur;

	int len = 0;
	int loop_len = 0;

	printf( "ERT\n" );
	/* ERT */
	len = parseERThead(ptr, &erth);
	if(  erth.table_id != 0xD1U )
		return;

	printf( "%04X\n", erth.information_provider_id );
	/* ERT ヘッダから、どのSVTのERT情報か特定する */
	svtcur = svttop->next;
	while(1){
		if( svtcur ){
			// 真のスロット番号を取得するつもりだったがTSにPID:0x21が含まれていなかったため頓挫(PMTを追うのも面倒)
			// なおarib仕様そのものに不具合があると思われる
			if( ( svtcur->transport_stream_id & 0xF000U ) == 0x4000U && ( ( erth.information_provider_id & 0x0FF8U ) >> 4 ) == ( svtcur->transport_stream_id & 0x00FFU )  ){
				svtcur->slot = erth.information_provider_id & 0x0007U;
				return;
			}
			svtcur = svtcur->next;
		}else
			return;
	}

	ptr += len;
	loop_len = erth.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC
	while(loop_len > 0) {
		/* 連続する拡張イベントは、漢字コードが泣き別れして
		   分割されるようだ。連続かどうかは、item_description_lengthが
		   設定されているかどうかで判断できるようだ。 */

		len = parseERTbody(ptr, &ertb );
		ptr += len;
		loop_len -= len;
		// 以下略
		break;
	}

	return;
}
