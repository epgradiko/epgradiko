// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "ts_ctl.h"

int parseSDThead(unsigned char *data, SDThead *h) {
	int boff = 0;

	memset(h, 0, sizeof(SDThead));

	boff = 0;
	h->table_id = getBit(data, &boff, 8);
	h->section_syntax_indicator = getBit(data, &boff, 1);
	h->reserved_future_use1 = getBit(data, &boff, 1);
	h->reserved1 = getBit(data, &boff, 2);
	h->section_length = getBit(data, &boff, 12);
	h->transport_stream_id = getBit(data, &boff, 16);
	h->reserved2 = getBit(data, &boff, 2);
	h->version_number = getBit(data, &boff, 5);
	h->current_next_indicator = getBit(data, &boff, 1);
	h->section_number = getBit(data, &boff, 8);
	h->last_section_number = getBit(data, &boff, 8);
	h->original_network_id = getBit(data, &boff, 16);
	h->reserved_future_use2 = getBit(data, &boff, 8);

	return 11;
}

int parseSDTbody(unsigned char *data, SDTbody *b) {
	int boff = 0;

	memset(b, 0, sizeof(SDTbody));

	b->service_id = getBit(data, &boff, 16);
	b->reserved_future_use1 = getBit(data, &boff, 3);
	b->EIT_user_defined_flags = getBit(data, &boff, 3);
	b->EIT_schedule_flag = getBit(data, &boff, 1);
	b->EIT_present_following_flag = getBit(data, &boff, 1);
	b->running_status = getBit(data, &boff, 3);
	b->free_CA_mode = getBit(data, &boff, 1);
	b->descriptors_loop_length = getBit(data, &boff, 12);

	return 5;
}

int parseSVCdesc(unsigned char *data, SVCdesc *desc) {
//0x48のサービス記述子、放送局の名前などが入っているよう
	int boff = 0;

	memset(desc, 0, sizeof(SVCdesc));
	desc->descriptor_tag = getBit(data, &boff, 8);
	desc->descriptor_length = getBit(data, &boff, 8);
	desc->service_type = getBit(data, &boff, 8);
	desc->service_provider_name_length = getBit(data, &boff, 8);
	getStr(desc->service_provider_name, data, &boff, desc->service_provider_name_length);
	desc->service_name_length = getBit(data, &boff, 8);
	getStr(desc->service_name, data, &boff, desc->service_name_length);

	return desc->descriptor_length + 2;
}

int parseLOGdesc(unsigned char *data, LOGdesc *desc) {
//0xC1のデジタルコピー制御記述子
//0xCFのロゴ伝送記述子
	int boff = 0;

	memset(desc, 0, sizeof(LOGdesc));
	desc->descriptor_tag = getBit(data, &boff, 8);
	desc->descriptor_length = getBit(data, &boff, 8);
	desc->logo_transmission_type = getBit(data, &boff, 8);
	if ( desc->logo_transmission_type == 0x01 ) {
		desc->reserved_future_use1 = getBit(data, &boff, 7);
		desc->logo_id = getBit(data, &boff, 9);
		desc->reserved_future_use2 = getBit(data, &boff, 4);
		desc->logo_version = getBit(data, &boff, 12);
		desc->download_data_id = getBit(data, &boff, 16);
	}
	else if ( desc->logo_transmission_type == 0x02 ) {
		desc->reserved_future_use1 = getBit(data, &boff, 7);
		desc->logo_id = getBit(data, &boff, 9);
	}
	else if ( desc->logo_transmission_type == 0x03 ) {
		memcpy(desc->logo_char, data + boff / 8, desc->descriptor_length);
		// getStr(desc->logo_char, data, &boff, desc->descriptor_length);
	}

	return desc->descriptor_length + 2;
}

SVT_CONTROL *serachid(SVT_CONTROL *svttop, int service_id)
{
	SVT_CONTROL	*cur = svttop->next ;
	while(cur != NULL){
		if(cur->service_id == service_id){
			return cur;
		}
		cur = cur->next ;
	}
	return NULL;
}


void enqueue_sdt(SVT_CONTROL *svttop, SVT_CONTROL *sdtptr)
{
	SVT_CONTROL	*cur ;

	if( svttop->next == NULL ){
		svttop->next = sdtptr;
		sdtptr->prev = svttop;
		return;
	}
	cur = svttop->next;
	while(1){
		if( sdtptr->service_id < cur->service_id ){
			cur->prev->next = sdtptr;
			sdtptr->prev    = cur->prev;
			cur->prev       = sdtptr;
			sdtptr->next    = cur;
			return;
		}
		if( cur->next == NULL ){
			cur->next    = sdtptr;
			sdtptr->prev = cur;
			return;
		}
		cur = cur->next;
	}
}


int stat_service_type( int service_type, int service_id, int mode )
{
/*
サービス形式種別
0x00 未定義
0x01 デジタルＴＶサービス
0x02 デジタル音声サービス
0x03 - 0x7F 未定義
0x80 - 0xA0 事業者定義
0xA1 臨時映像サービス
0xA2 臨時音声サービス
0xA3 臨時データサービス
0xA4 エンジニアリングサービス
0xA5 プロモーション映像サービス
0xA6 プロモーション音声サービス
0xA7 プロモーションデータサービス
0xA8 事前蓄積用データサービス
0xA9 蓄積専用データサービス
0xAA ブックマーク一覧データサービス
0xAB サーバー型サイマルサービス
0xAC 独立ファイルサービス
0xAD - 0xBF 未定義（標準化機関定義領域）
0xC0 データサービス（ワンセグも）
0xC1 - 0xFF 未定義
*/
	switch( service_type ){
		case 0xC0:
			if( !mode && service_id != 910 )	// ＷＮＩ・９１０
				return 0;
		case 0x01:
		case 0x02:
			break;
		default:
			if( !mode )
				return 0;
			break;
	}
	return 1;
}


void dumpSDT(unsigned char *ptr, SVT_CONTROL *svttop, char *ontvheader, int select_sid, int mode)
{

	SDThead  sdth;
	SDTbody  sdtb;
	SVCdesc  desc;
	LOGdesc  logd;
	SVT_CONTROL	*svtptr;

	int len = 0;
	int loop_len = 0;
	int desc_len = 0;

	/* SDT */
	len = parseSDThead(ptr, &sdth); 
	ptr += len;
	loop_len = sdth.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC
	while(loop_len > 0) {
		len = parseSDTbody(ptr, &sdtb);
		//printf("body %d - %d = %d\n",loop_len,len,loop_len - len);
		ptr += len;
		loop_len -= len;

		desc_len = sdtb.descriptors_loop_length;
		loop_len -= desc_len;
		while(desc_len > 0) {
			if ( *ptr == 0xCF ) {
				len = parseLOGdesc(ptr, &logd);
				ptr += len;
				desc_len -= len;

				#if 0
				printf("LOG=%d,%d,%d,%d\n",
					logd.logo_transmission_type, logd.logo_id, 
					logd.logo_version, logd.download_data_id);
				#endif

				/*
					logd.logo_transmission_type
					0x01 CDT 伝送方式1:CDT をダウンロードデータ識別で直接参照する場合
					0x02 CDT 伝送方式2:CDT をロゴ識別を用いてダウンロードデータ識別を間接的に参照する場合
					0x03 簡易ロゴ方式
				*/
				if ( logd.logo_transmission_type != 0x01 )
					continue;
				svtptr = svttop;
				while( svtptr->next != NULL ){
					svtptr = svtptr->next;
					if( svtptr->service_id == sdtb.service_id ){
						svtptr->logo_download_data_id = logd.download_data_id;
						svtptr->logo_version          = logd.logo_version;
						break;
					}
				}
				continue;
			}
			else if ( *ptr != 0x48 ) {
				len = parseOTHERdesc(ptr);
				ptr += len;
				desc_len -= len;
				continue;
			}
			len = parseSVCdesc(ptr, &desc);
			//printf("desc %d - %d = %d\n",desc_len,len,desc_len - len);
			ptr += len;
			desc_len -= len;
			if( select_sid && select_sid!=sdtb.service_id )
				continue;

			svtptr = serachid(svttop, sdtb.service_id);
			if( svtptr == NULL ){
				svtptr = calloc(1, sizeof(SVT_CONTROL));
				svtptr->service_id          = sdtb.service_id;
				svtptr->service_type        = desc.service_type;
				svtptr->original_network_id = sdth.original_network_id;
				svtptr->transport_stream_id = sdth.transport_stream_id;
				memcpy( svtptr->servicename, desc.service_name, strlen(desc.service_name) );
				sprintf( svtptr->ontv, "%s_%d", ontvheader, sdtb.service_id );
				svtptr->eitsch = calloc( 1, sizeof(EIT_CONTROL) );
				svtptr->eit_pf = calloc( 1, sizeof(EIT_CONTROL) );
				svtptr->import_stat = stat_service_type( desc.service_type, sdtb.service_id, mode ) ? 2 : -2;
				enqueue_sdt( svttop, svtptr );
#if 0
				printf("STATION=%s,%d,%d,%d,%d,%d\n",
					desc.service_name,sdtb.service_id,sdth.transport_stream_id,
					sdth.original_network_id,sdtb.service_id,desc.service_type);

				printf("SDT=%s,%d,%x,%x,%x,%x,%x,%x,%x\n",
					desc.service_name, sdtb.service_id, sdtb.reserved_future_use1,
					sdtb.EIT_user_defined_flags, sdtb.EIT_schedule_flag, sdtb.EIT_present_following_flag,
					sdtb.running_status, sdtb.free_CA_mode, sdtb.descriptors_loop_length);
/*
0x01:デジタルTVサービス
0xA5:プロモーション映像サービス
0x0C:データサービス
*/
				printf("SDT=(%x:%x)%s,%d,%d,%d,%d,%d(%d,%x,%x,%x,%x,%x,%x,%x,%x,%x,%x,%x,%x,%x)\n",
						sdth.table_id, desc.service_type, 
						desc.service_name, sdtb.service_id,
						desc.descriptor_tag, desc.descriptor_length, desc.service_type,
						desc.service_provider_name_length, desc.service_name_length,
						sdth.table_id, sdth.section_syntax_indicator, sdth.reserved_future_use1,
						sdth.reserved1, sdth.section_length, sdth.transport_stream_id,
						sdth.reserved2, sdth.version_number, sdth.current_next_indicator,
						sdth.section_number, sdth.last_section_number, sdth.original_network_id,
						sdth.reserved_future_use2);
#endif
			}else
				if( svtptr->import_stat == 0 ){
					svtptr->service_type        = desc.service_type;
					svtptr->original_network_id = sdth.original_network_id;
					svtptr->transport_stream_id = sdth.transport_stream_id;
					memcpy( svtptr->servicename, desc.service_name, strlen(desc.service_name) );
					sprintf( svtptr->ontv, "%s_%d", ontvheader, sdtb.service_id );
					svtptr->import_stat = stat_service_type( desc.service_type, sdtb.service_id, mode ) ? 2 : -2;
				}else
					if( svtptr->import_stat == -1 ){
						svtptr->service_type        = desc.service_type;
						svtptr->original_network_id = sdth.original_network_id;
						svtptr->transport_stream_id = sdth.transport_stream_id;
						memcpy( svtptr->servicename, desc.service_name, strlen(desc.service_name) );
						sprintf( svtptr->ontv, "%s_%d", ontvheader, sdtb.service_id );
						svtptr->import_stat = 1;
					}
		}
/*
		//ptr += sdtb.descriptors_loop_length;
		loop_len -= sdtb.descriptors_loop_length;
		
		if (loop_len>0){
			ptr += sdtb.descriptors_loop_length;
		}
*/
	}
	return;
}

