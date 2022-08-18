char* parseComponentDescType(int componentDescType) {
	static char str[MAXSECLEN];
	memset(str, '\0', sizeof(str));
	char *strpart;

	switch (componentDescType & 0xF0) {
	case 0x00 :
		strpart = "映像480i ";
		break;
	case 0x90 :
		strpart = "映像2160p ";
		break;
	case 0xA0 :
		strpart = "映像480p ";
		break;
	case 0xB0 :
		strpart = "映像1080i ";
		break;
	case 0xC0 :
		strpart = "映像720p ";
		break;
	case 0xD0 :
		strpart = "映像240p ";
		break;
	case 0xE0 :
		strpart = "映像1080p ";
		break;
	default :
		strpart = "映像不明 ";
		break;
	}
	strcat(str, strpart);

	switch (componentDescType & 0x0F) {
	case 0x01 :
		strpart = "アスペクト比4:3";
		break;
	case 0x02 :
		strpart = "アスペクト比16:9 パンベクトルあり";
		break;
	case 0x03 :
		strpart = "アスペクト比16:9 パンベクトルなし";
		break;
	case 0x04 :
		strpart = "アスペクト比 > 16:9";
		break;
	default :
		strpart = "アスペクト比不明";
		break;
	}
	strcat(str, strpart);

	return str;
}

char* parseAudioComponentDescType(int AudiocomponentDescType) {
	static char str[MAXSECLEN];
	memset(str, '\0', sizeof(str));
	char *strpart;

	switch (AudiocomponentDescType) {
	case 0x01 :
		strpart = "音声1/0モード(シングルモノ)";
		break;
	case 0x02 :
		strpart = "音声1/0+1/0モード(デュアルモノ)";
		break;
	case 0x03 :
		strpart = "音声2/0モード(ステレオ)";
		break;
	case 0x04 :
		strpart = "音声2/1モード";
		break;
	case 0x05 :
		strpart = "音声3/0モード";
		break;
	case 0x06 :
		strpart = "音声2/2モード";
		break;
	case 0x07 :
		strpart = "音声3/1モード";
		break;
	case 0x08 :
		strpart = "音声3/2モード";
		break;
	case 0x09 :
		strpart = "音声3/2+LFEモード(3/2.1モード)";
		break;
	case 0x0A :
		strpart = "音声3/3.1モード";
		break;
	case 0x0B :
		strpart = "音声2/0/0-2/0/2-0.1モード";
		break;
	case 0x0C :
		strpart = "音声5/2.1モード";
		break;
	case 0x0D :
		strpart = "音声3/2/2.1モード";
		break;
	case 0x0E :
		strpart = "音声2/0/0-3/0/2-0.1モード";
		break;
	case 0x0F :
		strpart = "音声0/2/0-3/0/2-0.1モード";
		break;
	case 0x10 :
		strpart = "音声2/0/0-3/2/3-0.2モード";
		break;
	case 0x11 :
		strpart = "音声3/3/3-5/2/3-3/0/0.2モード";
		break;
	case 0x40 :
		strpart = "音声視覚障害者用解説";
		break;
	case 0x41 :
		strpart = "音声聴覚障害者用";
		break;
	default :
		strpart = "音声不明";
		break;
	}
	strcat(str, strpart);

	return str;
