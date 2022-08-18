// データ放送用プログラム詰め合わせ その2より流用
// カラーマップデータをPNGに適用する

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define bool char
#define true  1
#define false 0
#define inline

typedef unsigned char byte;
typedef unsigned short uint16;
typedef unsigned long uint32;

typedef struct _rgb_color {
	byte r;
	byte g;
	byte b;
	byte a;
} rgb_color;

typedef struct _color_map_data {
	byte clut_type;
	byte depth;
	byte region_flag;
	byte start_end_flag;
	byte reserved_future_use;
	uint16 top_left_x;
	uint16 top_left_y;
	uint16 bottom_right_x;
	uint16 bottom_right_y;
	uint16 start_index;
	uint16 end_index;
	rgb_color color_map[0x10000];
} color_map_data;

static const rgb_color default_colormap[128] = {
	{  0,	  0,	  0,	255},
	{255,	  0,	  0,	255},
	{  0,	255,	  0,	255},
	{255,	255,	  0,	255},
	{  0,	  0,	  0,	255},
	{255,	  0,	255,	255},
	{  0,	255,	255,	255},
	{255,	255,	255,	255},
	{  0,	  0,	  0,	  0},
	{170,	  0,	  0,	255},
	{  0,	170,	  0,	255},
	{170,	170,	  0,	255},
	{  0,	  0,	170,	255},
	{170,	  0,	170,	255},
	{  0,	170,	170,	255},
	{170,	170,	170,	255},
	{  0,	  0,	 85,	255},
	{  0,	 85,	  0,	255},
	{  0,	 85,	 85,	255},
	{  0,	 85,	170,	255},
	{  0,	 85,	255,	255},
	{  0,	170,	 85,	255},
	{  0,	170,	255,	255},
	{  0,	255,	 85,	255},
	{  0,	255,	170,	255},
	{ 85,	  0,	  0,	255},
	{ 85,	  0,	 85,	255},
	{ 85,	  0,	170,	255},
	{ 85,	  0,	255,	255},
	{ 85,	 85,	  0,	255},
	{ 85,	 85,	 85,	255},
	{ 85,	 85,	170,	255},
	{ 85,	 85,	255,	255},
	{ 85,	170,	  0,	255},
	{ 85,	170,	 85,	255},
	{ 85,	170,	170,	255},
	{ 85,	170,	255,	255},
	{ 85,	255,	  0,	255},
	{ 85,	255,	 85,	255},
	{ 85,	255,	170,	255},
	{ 85,	255,	255,	255},
	{170,	  0,	 85,	255},
	{170,	  0,	255,	255},
	{170,	 85,	  0,	255},
	{170,	 85,	 85,	255},
	{170,	 85,	170,	255},
	{170,	 85,	255,	255},
	{170,	170,	 85,	255},
	{170,	170,	255,	255},
	{170,	255,	  0,	255},
	{170,	255,	 85,	255},
	{170,	255,	170,	255},
	{170,	255,	255,	255},
	{255,	  0,	 85,	255},
	{255,	  0,	170,	255},
	{255,	 85,	  0,	255},
	{255,	 85,	 85,	255},
	{255,	 85,	170,	255},
	{255,	 85,	255,	255},
	{255,	170,	  0,	255},
	{255,	170,	 85,	255},
	{255,	170,	170,	255},
	{255,	170,	255,	255},
	{255,	225,	 85,	255},
	{225,	225,	170,	255},
	{  0,	  0,	  0,	128},
	{255,	  0,	  0,	128},
	{  0,	255,	  0,	128},
	{255,	255,	  0,	128},
	{  0,	  0,	255,	128},
	{255,	  0,	255,	128},
	{  0,	255,	255,	128},
	{255,	255,	255,	128},
	{170,	  0,	  0,	128},
	{  0,	170,	  0,	128},
	{170,	170,	  0,	128},
	{  0,	  0,	170,	128},
	{170,	  0,	170,	128},
	{  0,	170,	170,	128},
	{170,	170,	170,	128},
	{  0,	  0,	 85,	128},
	{  0,	 85,	  0,	128},
	{  0,	 85,	 85,	128},
	{  0,	 85,	170,	128},
	{  0,	 85,	255,	128},
	{  0,	170,	 85,	128},
	{  0,	170,	255,	128},
	{  0,	255,	 85,	128},
	{  0,	255,	170,	128},
	{ 85,	  0,	  0,	128},
	{ 85,	  0,	 85,	128},
	{ 85,	  0,	170,	128},
	{ 85,	  0,	255,	128},
	{ 85,	 85,	  0,	128},
	{ 85,	 85,	 85,	128},
	{ 85,	 85,	170,	128},
	{ 85,	 85,	255,	128},
	{ 85,	170,	  0,	128},
	{ 85,	170,	 85,	128},
	{ 85,	170,	170,	128},
	{ 85,	170,	255,	128},
	{ 85,	255,	  0,	128},
	{ 85,	255,	 85,	128},
	{ 85,	255,	170,	128},
	{ 85,	255,	255,	128},
	{170,	  0,	 85,	128},
	{170,	  0,	255,	128},
	{170,	 85,	  0,	128},
	{170,	 85,	 85,	128},
	{170,	 85,	170,	128},
	{170,	 85,	255,	128},
	{170,	170,	 85,	128},
	{170,	170,	255,	128},
	{170,	255,	  0,	128},
	{170,	255,	 85,	128},
	{170,	255,	170,	128},
	{170,	255,	255,	128},
	{255,	  0,	 85,	128},
	{255,	  0,	170,	128},
	{255,	 85,	  0,	128},
	{255,	 85,	 85,	128},
	{255,	 85,	170,	128},
	{255,	 85,	255,	128},
	{255,	170,	  0,	128},
	{255,	170,	 85,	128},
	{255,	170,	170,	128},
	{255,	170,	255,	128},
	{255,	255,	 85,	128},
};


/*
	Y  = 0.2126 * R + 0.7152 * G + 0.0722 * B
	Cb = -(0.2126/1.8556)*(224/219) * R -(0.7152/1.8556)*(224/219) * G 0.5*(224/219) * B
	Cr = 0.5*(224/219) * R -(0.7152/1.5748)*(224/219) * G -(0.0722/1.5748)*(224/219) * B
*/

inline byte ycbcr_to_r(int y,int cb,int cr)
{
	int r=(76309*(y-16)+104597*(cr-128)+32768)/65536;
	return (byte)(r<0?0:r>255?255:r);
}

inline byte ycbcr_to_g(int y,int cb,int cr)
{
	int g=(76309*(y-16)-25675*(cb-128)-53279*(cr-128)+32768)/65536;
	return (byte)(g<0?0:g>255?255:g);
}

inline byte ycbcr_to_b(int y,int cb,int cr)
{
	int b=(76309*(y-16)+132201*(cb-128)+32768)/65536;
	return (byte)(b<0?0:b>255?255:b);
}


byte read_byte(void *fp, int *index_fp)
{
	int data = *((unsigned char*)fp + *index_fp++);
	return (byte)data;
}


uint16 read_word(void *fp, int *index_fp)
{
	byte data=read_byte(fp, index_fp);

	return ((uint16)data<<8)|(uint16)read_byte(fp, index_fp);
}


uint32 read_dword(void *fp, int *index_fp)
{
	byte data1=read_byte(fp, index_fp);
	byte data2=read_byte(fp, index_fp);
	byte data3=read_byte(fp, index_fp);
	byte data4=read_byte(fp, index_fp);

	return ((uint32)data1<<24)|((uint32)data2<<16)|((uint32)data3<<8)|(uint32)data4;
}


void read_data(void *fp,int *index_fp,void *data,size_t size)
{
	memcpy(data,fp + *index_fp, size);
	*index_fp += size;
}


void write_dword(void **fp, int *index_fp,uint32 value)
{
	byte data[4];

	data[0]=(byte)(value>>24);
	data[1]=(byte)((value>>16)&0xFF);
	data[2]=(byte)((value>>8)&0xFF);
	data[3]=(byte)(value&0xFF);
	*fp = realloc(*fp, *index_fp + 4);
	memcpy(*fp + *index_fp, data, 4);
	*index_fp += 4;
}


void write_data(void **fp,int *index_fp,void *data,size_t size)
{
	*fp = realloc(*fp, *index_fp + size);
	memcpy(*fp + *index_fp, data, size);
	*index_fp += size;
}


uint32 calc_crc(const byte *data,size_t size)
{
	static const uint32 crc_table[256] = {
		0x00000000, 0x77073096, 0xEE0E612C, 0x990951BA,
		0x076DC419, 0x706AF48F, 0xE963A535, 0x9E6495A3,
		0x0EDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988,
		0x09B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91,
		0x1DB71064, 0x6AB020F2, 0xF3B97148, 0x84BE41DE,
		0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7,
		0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC,
		0x14015C4F, 0x63066CD9, 0xFA0F3D63, 0x8D080DF5,
		0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172,
		0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B,
		0x35B5A8FA, 0x42B2986C, 0xDBBBC9D6, 0xACBCF940,
		0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59,
		0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116,
		0x21B4F4B5, 0x56B3C423, 0xCFBA9599, 0xB8BDA50F,
		0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924,
		0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D,
		0x76DC4190, 0x01DB7106, 0x98D220BC, 0xEFD5102A,
		0x71B18589, 0x06B6B51F, 0x9FBFE4A5, 0xE8B8D433,
		0x7807C9A2, 0x0F00F934, 0x9609A88E, 0xE10E9818,
		0x7F6A0DBB, 0x086D3D2D, 0x91646C97, 0xE6635C01,
		0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E,
		0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457,
		0x65B0D9C6, 0x12B7E950, 0x8BBEB8EA, 0xFCB9887C,
		0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65,
		0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2,
		0x4ADFA541, 0x3DD895D7, 0xA4D1C46D, 0xD3D6F4FB,
		0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0,
		0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9,
		0x5005713C, 0x270241AA, 0xBE0B1010, 0xC90C2086,
		0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F,
		0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4,
		0x59B33D17, 0x2EB40D81, 0xB7BD5C3B, 0xC0BA6CAD,
		0xEDB88320, 0x9ABFB3B6, 0x03B6E20C, 0x74B1D29A,
		0xEAD54739, 0x9DD277AF, 0x04DB2615, 0x73DC1683,
		0xE3630B12, 0x94643B84, 0x0D6D6A3E, 0x7A6A5AA8,
		0xE40ECF0B, 0x9309FF9D, 0x0A00AE27, 0x7D079EB1,
		0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE,
		0xF762575D, 0x806567CB, 0x196C3671, 0x6E6B06E7,
		0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC,
		0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5,
		0xD6D6A3E8, 0xA1D1937E, 0x38D8C2C4, 0x4FDFF252,
		0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B,
		0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60,
		0xDF60EFC3, 0xA867DF55, 0x316E8EEF, 0x4669BE79,
		0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236,
		0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F,
		0xC5BA3BBE, 0xB2BD0B28, 0x2BB45A92, 0x5CB36A04,
		0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D,
		0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x026D930A,
		0x9C0906A9, 0xEB0E363F, 0x72076785, 0x05005713,
		0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0x0CB61B38,
 		0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0x0BDBDF21,
		0x86D3D2D4, 0xF1D4E242, 0x68DDB3F8, 0x1FDA836E,
		0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777,
		0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C,
		0x8F659EFF, 0xF862AE69, 0x616BFFD3, 0x166CCF45,
		0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2,
		0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB,
		0xAED16A4A, 0xD9D65ADC, 0x40DF0B66, 0x37D83BF0,
		0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9,
		0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6,
		0xBAD03605, 0xCDD70693, 0x54DE5729, 0x23D967BF,
		0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94,
		0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D,
	};
	uint32 crc;

	crc=0xFFFFFFFFUL;
	for (size_t i=0;i<size;i++)
		crc=(crc>>8)^crc_table[(crc^data[i])&0x000000FFUL];
	return ~crc;
}


int clt2png(void* input, void** output, int* output_size)
{
	int input_index = 0, output_index = 0;

	color_map_data colormap;
	memset(colormap.color_map,0,sizeof(colormap.color_map));
	memcpy(colormap.color_map,default_colormap,sizeof(default_colormap));

	if (input!=NULL) {
		// Parse color_map_data
		byte b=read_byte(input, &input_index);
		colormap.clut_type=(b&0x80)>>7;
		colormap.depth=(b&0x60)>>5;
		if (colormap.depth==3) {
			printf("Error : Unknown depth (%d)\n",colormap.depth);
			return 1;
		}
		colormap.region_flag=(b&0x10)>>4;
		colormap.start_end_flag=(b&0x08)>>3;
		colormap.reserved_future_use=b&0x07;
		if (colormap.region_flag) {
			colormap.top_left_x=read_word(input, &input_index);
			colormap.top_left_y=read_word(input, &input_index);
			colormap.bottom_right_x=read_word(input, &input_index);
			colormap.bottom_right_y=read_word(input, &input_index);
		}
		if (colormap.start_end_flag) {
			if (colormap.depth==0) {
				b=read_byte(input, &input_index);
				colormap.start_index=b>>4;
				colormap.end_index=b&0x0F;
			} else if (colormap.depth==1) {
				colormap.start_index=read_byte(input, &input_index);
				colormap.end_index=read_byte(input, &input_index);
			} else if (colormap.depth==2) {
				colormap.start_index=read_word(input, &input_index);
				colormap.end_index=read_word(input, &input_index);
			}
		} else {
			colormap.start_index=0;
			switch (colormap.depth) {
			case 0:	colormap.end_index=15;		break;
			case 1:	colormap.end_index=255;		break;
			case 2:	colormap.end_index=65535;	break;
			}
		}
		for (uint16 i=colormap.start_index;i<=colormap.end_index;i++) {
			if (colormap.clut_type==0) {
				int y,cb,cr;
				y=read_byte(input, &input_index);
				cb=read_byte(input, &input_index);
				cr=read_byte(input, &input_index);
				colormap.color_map[i].r=ycbcr_to_r(y,cb,cr);
				colormap.color_map[i].g=ycbcr_to_g(y,cb,cr);
				colormap.color_map[i].b=ycbcr_to_b(y,cb,cr);
			} else {
				colormap.color_map[i].r=read_byte(input, &input_index);
				colormap.color_map[i].g=read_byte(input, &input_index);
				colormap.color_map[i].b=read_byte(input, &input_index);
			}
			colormap.color_map[i].a=read_byte(input, &input_index);
		}
		input_index = 0;
	}

	// Open PNG file

	byte buffer[8];
	read_data(input,&input_index,buffer,8);
	if (memcmp(buffer,"\x89PNG\r\n\x1A\n",8)!=0) {
		printf("Error : Not a PNG format\n");
		return 1;
	}
	write_data(output,&output_index,buffer,8);
	int bit_depth=0,color_type=-1;
	bool has_palette=false;
	while (true) {
		read_data(input,&input_index,buffer,8);
		uint32 data_size=((uint32)buffer[0]<<24)|((uint32)buffer[1]<<16)|((uint32)buffer[2]<<8)|(uint32)buffer[3];
		if (memcmp(buffer+4,"IHDR",4)==0) {
			byte header[13];
			read_data(input,&input_index,header,13);
			bit_depth=header[8];
			color_type=header[9];
			input_index -= 13;
		} else if (memcmp(buffer+4,"PLTE",4)==0) {
			has_palette=true;
		} else if (memcmp(buffer+4,"IDAT",4)==0) {
			if (!has_palette && bit_depth<=8 && color_type==3) {
				int num_colors=1<<bit_depth;
				byte data[4+256*3];
				int i;
				uint32 crc;

				// tRNS
				for (i=0;i<num_colors;i++) {
					if (colormap.color_map[i].a<255)
						break;
				}
				if (i<num_colors) {
					write_dword(output,&output_index,num_colors);
					memcpy(data,"tRNS",4);
					for (int i=0;i<num_colors;i++)
						data[4+i]=colormap.color_map[i].a;
					write_data(output,&output_index,data,4+num_colors);
					crc=calc_crc(data,4+num_colors);
					write_dword(output,&output_index,crc);
				}

				// Generate PLTE chunk
				uint32 palette_size=num_colors*3;
				write_dword(output,&output_index,palette_size);
				memcpy(data,"PLTE",4);
				for (i=0;i<num_colors;i++) {
					data[4+i*3+0]=colormap.color_map[i].r;
					data[4+i*3+1]=colormap.color_map[i].g;
					data[4+i*3+2]=colormap.color_map[i].b;
				}
				write_data(output,&output_index,data,4+palette_size);
				crc=calc_crc(data,4+palette_size);
				write_dword(output,&output_index,crc);
			}
		}
		// Copy chunk
		write_data(output,&output_index,buffer,8);
		void *data = malloc(data_size+4);
		read_data(input,&input_index,data,data_size+4);
		write_data(output,&output_index,data,data_size+4);
		free(data);
		if (memcmp(buffer+4,"IEND",4)==0)
			break;
	}

	*output_size = output_index;

	return 0;
}

