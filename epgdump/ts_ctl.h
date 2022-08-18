#ifndef	__TS_CONTROL_H__
#define	__TS_CONTROL_H__
/*
#include <time.h>
#include	"util.h"
*/
#include "sdt.h"

#define	CERTAINTY				0x00U
#define	START_TIME_UNCERTAINTY	0x01U
#define	DURATION_UNCERTAINTY	0x02U
#define	EVENT_UNCERTAINTY		0x03U
#define	NEXT_EVENT_UNCERTAINTY	0x04U


typedef	struct	_EIT_CONTROL	EIT_CONTROL;
struct	_EIT_CONTROL{
	EIT_CONTROL		*next ;
	EIT_CONTROL		*prev ;
	int				table_id ;
	int				servid ;
	int				event_id ;			// イベントID
	int				version_number;
	int				section_number;
	int				last_section_number;
	int				segment_last_section_number;
	int				running_status;
	int				free_CA_mode;
	int				content_type ;		// コンテントタイプ
	int				content_subtype ;		// コンテントサブタイプ
	int				genre2;
	int				sub_genre2;
	int				genre3;
	int				sub_genre3;
	int				episode_number;
	int				yy;
	int				mm;
	int				dd;
	int				hh;
	int				hm;
	int				ss;
	int				duration;
	char			*title;			// タイトル
	char			*subtitle;			// サブタイトル
	char			*desc;				// Description
	int				desc_length;			// Description Length
	int				video_type;			// 映像のタイプ
	int				audio_type;			// 音声のタイプ
	int				multi_type;			// 音声の 2 カ国語多重
	int				event_status;
	int				sch_pnt;
	int				import_cnt;
	int				renew_cnt;			// 更新カウンタ
	int				TID;
	int				TID_status;
};

typedef	struct	_SVT_CONTROL	SVT_CONTROL;
struct	_SVT_CONTROL{
	SVT_CONTROL		*next ;
	SVT_CONTROL		*prev ;
	int				service_id;			// イベントID > 実はサービスID
	int				service_type;		// サービス形式種別
	int				original_network_id ;			// OriginalNetworkID
	unsigned int	transport_stream_id ;			// TransportStreamID
	int				slot;
	char			servicename[MAXSECLEN] ;		// サービス名
	char			ontv[16];
	EIT_CONTROL		*eitsch;
	EIT_CONTROL		*eit_pf;
	EIT_CONTROL		*prev_sch;
#if 0
	EIT_CONTROL		*start_eit;
	int				start_eid;
	int				start_sid;
#endif
	int				import_cnt;
	int				import_stat;
	unsigned int	logo_download_data_id;
	unsigned int	logo_version;
	LOGO			logo_array[6];
};

typedef	struct	_EIT_NULLSEGMENT	EIT_NULLSEGMENT;
struct	_EIT_NULLSEGMENT{
	EIT_NULLSEGMENT		*next;
	EIT_NULLSEGMENT		*prev;
	int					table_id;
	int					service_id;
	int					section_number;
	int					version_number;
};

typedef	struct	_DSM_CONTROL	DSM_CONTROL;
struct	_DSM_CONTROL{
	int		isUsed;
	int		moduleId;
	int		lastBlockNumber;
	int		blockSize;
	void	*blockData;
};

#ifdef __cplusplus
extern "C"{
#endif /* __cplusplus */

	time_t timeParse( EIT_CONTROL *cur );
	void dateParse( EIT_CONTROL *dts, time_t *src );

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif
