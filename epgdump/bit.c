// -*- tab-width:4 -*-

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "bit.h"

// 2-STD-B10v4_6.pdf p89 (101/399)
int parseBIThead(unsigned char *data, BIThead *head) {
	int boff = 0;

	memset(head, 0, sizeof(BIThead));

	head->table_id = getBit(data, &boff, 8);
	head->section_syntax_indicator = getBit(data, &boff, 1);
	head->reserved_future_use1 = getBit(data, &boff, 1);
	head->reserved1 = getBit(data, &boff, 2);
	head->section_length = getBit(data, &boff, 12);
	head->original_network_id = getBit(data, &boff, 16);
	head->reserved2 = getBit(data, &boff, 2);
	head->version_number = getBit(data, &boff, 5);
	head->current_next_indicator = getBit(data, &boff, 1);
	head->section_number = getBit(data, &boff, 8);
	head->last_section_number = getBit(data, &boff, 8);
	head->reserved_future_use2 = getBit(data, &boff, 3);
	head->broadcast_view_propriety = getBit(data, &boff, 1);
	head->first_descriptors_length = getBit(data, &boff, 12);
	return 10;
}

// SI伝送パラメータ記述子
int parseBITdesc(unsigned char *data, BITdesc *desc) {
	int boff = 0;

	memset(desc, 0, sizeof(BITdesc));

	desc->descriptor_tag = getBit(data, &boff, 8);
	desc->descriptor_length = getBit(data, &boff, 8);
	desc->parameter_version = getBit(data, &boff, 8);
	desc->update_time = getBit(data, &boff,16);

	return 5;
}

// 4-TR-B14v4_3-2p3.pdf p4-254 (276/543)
int parseBITloop(unsigned char *data, BITloop *loop) {
	int boff = 0;

	memset(loop, 0, sizeof(BITloop));

	loop->broadcaster_id = getBit(data, &boff, 8);
	loop->reserved_future_use = getBit(data, &boff, 4);
	loop->broadcaster_descriptors_length = getBit(data, &boff, 12);

	return 3;
}

int parseBITtable(unsigned char *data, BITtable *table) {
	int boff = 0;

	memset(table, 0, sizeof(BITtable));

	table->table_id = getBit(data, &boff, 8);
	table->table_description_length = getBit(data, &boff, 8);
	table->table_cycle = getBit(data, &boff, 16);

	return table->table_description_length + 2;
}


void putBIT( unsigned char *ptr, BIThead *bith, int table_len )
{
	BITtable      bitt;
	int           media_type;
	int           pattern;
	unsigned char schedule_range;
	unsigned char type;
	int           cycle_gp_cnt;
	unsigned int  base_cycle;
	int           boff;
	int           len;
	unsigned char *wk_ptr;
	char          *name = NULL;


	while(table_len > 0) {
		/*
			0x40 NIT
			0xC4 BIT
			0x42 SDT
			0xC3(SDTT)
			0xC8(CDT)
			0x4E H-EIT[pf](M-EIT,L-EIT)
			0x50(各局伝送パラメータ運用部分の H-EIT[schedule basic])
			0x58 (各局伝送パラメータ運用部分の H-EIT[schedule extended])
		*/
// printf( "table_len:%d  ", table_len );
		switch( *ptr ){
			case 0x50: // H-EIT[sch]
			case 0x58:
			case 0x60:
				type       = *ptr++;
				len        = *ptr++;
				wk_ptr     = ptr;
				table_len -= len + 2;
				ptr       += len;
				do{
					boff       = 0;
					media_type = getBit( wk_ptr, &boff, 2 );
					if( media_type ){
						pattern        = getBit( wk_ptr, &boff, 2 );
						boff          += 4;
						schedule_range = getBit( wk_ptr, &boff, 8 );
						base_cycle     = getBit( wk_ptr, &boff, 12 );
						boff          += 2;
						cycle_gp_cnt   = getBit( wk_ptr, &boff, 2 );
						wk_ptr        += 4;
						len           -= 4;
						printf( "H-EIT[schedule(0x%02x)]=media_type:%d pattern:%d schedule_range:%d base_cycle:%d cycle_gp_cnt:%d\n",
													type, media_type, pattern, BCD(schedule_range), WBCD(base_cycle), cycle_gp_cnt );
						for(int cnt=0; cnt<cycle_gp_cnt&&len>=2; cnt++ ){
							printf( "\tseg_cnt:%d cycle:%d\n", BCD(wk_ptr[0]), BCD(wk_ptr[1]) );
							wk_ptr += 2;
							len    -= 2;
						}
					}else{
						printf( "H-EIT[schedule(0x%02x)] 0x%02x,0x%02x,0x%02x,0x%02x\n", type, wk_ptr[0], wk_ptr[1], wk_ptr[2], wk_ptr[3] );
						break;
					}
				}while( len >= 4 );
				break;
			case 0x4E: // H-EIT[pf]
				if( bith->original_network_id >= 10 ){	// BS:4 CS:6,7 地デジ:other
					len = *++ptr;
					table_len -= len + 2;
					ptr++;
					printf( "H-EIT[pf]:%d M-EIT:%d(%d) L-EIT:%d(%d)\n", BCD(ptr[0]), BCD(ptr[1]), ptr[3]>>4, BCD(ptr[2]), ptr[3]&0x0F );
					ptr += 4;
					break;
				}
			case 0x4F: // H-EIT[pf]
			case 0x40: // NIT
			case 0x41: // NIT
			case 0xC4: // BIT
			case 0x42: // SDT
			case 0x46: // SDT
			case 0xC5: // NBIT[msg]
			case 0xC6: // NBIT[ref]
				switch( *ptr ){
					case 0x4E: // H-EIT[pf]
						name =   "H-EIT[pf]";
						break;
					case 0x4F: // H-EIT[pf]
						name =   "H-EIT[pf]";
						break;
					case 0x40: // NIT
						name =   "NIT";
						break;
					case 0x41: // NIT
						name =   "NIT";
						break;
					case 0xC4: // BIT
						name =   "BIT";
						break;
					case 0x42: // SDT
						name =   "SDT";
						break;
					case 0x46: // SDT
						name =   "SDT";
						break;
					case 0xC5: // NBIT[msg]
						name =   "NBIT[msg]";
						break;
					case 0xC6: // NBIT[ref]
						name =   "NBIT[ref]";
						break;
				}
				len        = ptr[1] + 2;
				table_len -= len;
				printf("%s(0x%02x) desc_len:%d cycle:%d\n", name, ptr[0], ptr[1], BCD(ptr[2]) );
				ptr += len;
				break;
			case 0xC3: // (SDTT)
			case 0xC7: // LDT
			case 0xC8: // (CDT)
				switch( *ptr ){
					case 0xC3: // (SDTT)
						name =   "SDTT";
						break;
					case 0xC7: // LDT
						name =   "SDTT";
						break;
					case 0xC8: // (CDT)
						name =   "SDTT";
						break;
				}
				len = parseBITtable(ptr, &bitt);
				ptr       += len;
				table_len -= len;
				printf("%s(0x%02x) desc_len:%d cycle:%d\n", name, bitt.table_id, bitt.table_description_length, WBCD(bitt.table_cycle) );
				break;
			default:
				len = parseBITtable(ptr, &bitt);
				ptr       += len;
				printf("unknown(0x%02x) table_len:%d desc_len:%d cycle:%d\n", bitt.table_id, table_len, bitt.table_description_length, WBCD(bitt.table_cycle) );
				table_len -= len;
				return;
		}
	}
}


void dumpBIT(unsigned char *ptr)
{
	BIThead   bith;
	BITloop   bitl;
	BITdesc   bitd;

	int len = 0;
	int loop_len = 0;
	int desc_len = 0;
	int table_len = 0;

	/* BIT */
	len = parseBIThead(ptr, &bith);

	if( bith.table_id!=0xC4 || !bith.section_syntax_indicator )
		return;
	ptr += len;
	loop_len = bith.section_length - (len - 3 + 4); // 3は共通ヘッダ長 4はCRC
	if( bith.first_descriptors_length ){
		len       = parseBITdesc(ptr, &bitd);
		printf("\n[descriptor_tag:0x%02x] descriptor_len:%d 1st_descriptors_len:%d\n", bitd.descriptor_tag, bitd.descriptor_length, bith.first_descriptors_length );
		if ( bitd.descriptor_tag == 0xD7 ){
			table_len = bitd.descriptor_length - 3;

			putBIT( ptr+len, &bith, table_len );
		}
		ptr      += bith.first_descriptors_length;
		loop_len -= bith.first_descriptors_length;
#if 0
		if ( bitd.descriptor_tag == 0xD7 ){
			ptr      += len;

			table_len = bitd.descriptor_length - 3;
			loop_len -= bitd.descriptor_length + 2;

			putBIT( ptr, &bith, table_len );
			ptr += table_len;
		}else{
			len       = bitd.descriptor_length + 2;
			ptr      += len;
			loop_len -= len;
		}
#endif
	}

	while(loop_len > 0) {
		len       = parseBITloop(ptr, &bitl);
		ptr      += len;

		desc_len  = bitl.broadcaster_descriptors_length;
		printf("\n[broadcaster_id:%d] descriptors_len:%d loop_len:%d desc_len:%d\n", bitl.broadcaster_id, bitl.broadcaster_descriptors_length, loop_len, desc_len );
		loop_len -= desc_len + len;

		while(desc_len > 0) {
			int           boff = 0;
			char  buf[32];
			len = parseBITdesc(ptr, &bitd);
			printf("[descriptor_tag:0x%02x] descriptor_len:%d\n", bitd.descriptor_tag, bitd.descriptor_length );
			switch( bitd.descriptor_tag ){
				case 0xD7:
					ptr      += len;
					table_len = bitd.descriptor_length - 3;
					desc_len -= bitd.descriptor_length + 2;
					putBIT( ptr, &bith, table_len );
					ptr += table_len;
					break;
				case 0xD8:
					len       = bitd.descriptor_length;
					ptr      += 2;
					getStr( buf, ptr, &boff, bitd.descriptor_length );
					printf( "%s\n", buf );
					ptr      += bitd.descriptor_length;
					desc_len -= bitd.descriptor_length + 2;
					break;
				default:
					len       = bitd.descriptor_length + 2;
					ptr      += len;
					desc_len -= len;
					break;
			}
		}
	}

	return;
}
