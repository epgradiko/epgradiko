xmltv-epg

MPEG-TS�Ɋ܂܂��epg��xml�ŏo�͂���v���O�����ł��B
��N/E9PqspSk����recfriio Solaris��(http://2sen.dip.jp/cgi-bin/friioup/source/up0737.zip)�Ɋ܂܂��epgdump��
Linux�ł������������̂��x�[�X��xmltv�p��xml�t�@�C�����쐬���܂��B

�܂��A�^�C�g�����Ɋ܂܂��
    "�@��"��A"�@�u"�A"�@��"�A"�i"�A"�u"�A���T�u�^�C�g���Ƃ��Ĉ����l�ɏC�����Ă��܂��B
��F

�^�C�g�����A
�����̂悢���I�u���̎��N�̓����������v
�̏ꍇ
�����̂悢���I���^�C�g���ɁA�u���̎��N�̓����������v���T�u�^�C�g���ɒǉ�����܂��B

�T�u�^�C�g���Ƃ��Ĉ������̂𑝂₷�ꍇ�́Aeit.c�ɂ���subtitle_cnv_str�ɒǉ����Ă��������B

�g�p���@�͈ȉ��̒ʂ�ł��B

Usage : ./epgdump {/BS|/CS} <tsFile> <outfile> {-pf}
Usage : ./epgdump <id> <tsFile> <outfile> {-pf}

id       �`�����l�����ʎq�B�n��g�̕����`�����l����^���܂��B
/BS      BS���[�h�B���TS����BS�S�ǂ̃f�[�^��ǂݍ��݂܂��B
/CS      CS���[�h�B���TS���畡���ǂ̃f�[�^��ǂݍ��݂܂��B
/TIME    �������킹���[�h�BTS����TOT(Time Offset Table)��ǂݍ��݂܂��B
         recpt1 <�C��> 10(�b�ȏ�) - | epgdump /TIME - <�C��>�̌`�Ŏg�p���Ă��������B
         TOT��5�b��1�񂵂����Ȃ����߁Arecpt1�ɗ^���鎞�Ԃ�������x�������Ă��������B
-pf      EID[pf]�P�Əo�̓��[�h�B�K�v��TS�̒���4�b�ł��B
-sid n   BS/CS�P�`�����l���o�̓��[�h�Bn�ɂ̓`�����l��sid�����
-cut n   BS/CS�s�v�`�����l�����O���[�h�Bn�ɂ͕s�v�`�����l��sid��csv�`���œ���

make�����epgdump���r���h����܂��B

epgdump���C�Z���X(Solaris�ł����p):
>epgdump�Ɋւ��ẮABonTest Ver.1.40���炻�̂܂܃\�[�X�������Ă��Ă��镔����
>���邽�߁A���̃��C�Z���X�ɏ]�����܂��B
>BonTest��Readme.txt���
>>
>>�R�D���C�Z���X�ɂ���
>>�@�@�E�{�p�b�P�[�W�Ɋ܂܂��S�Ẵ\�[�X�R�[�h�A�o�C�i���ɂ��Ē��쌠�͈�؎咣���܂���B
>>�@�@�E�I���W�i���̂܂ܖ��͉��ς��A�e���̃\�t�g�E�F�A�Ɏ��R�ɓY�t�A�g�ݍ��ނ��Ƃ��ł��܂��B
>>�@�@�E�A��GPL�ɏ]�����Ƃ�v�����܂��̂ł������s���ꍇ�̓\�[�X�R�[�h�̊J�����K�{�ƂȂ�܂��B
>>�@�@�E���̂Ƃ��{�\�t�g�E�F�A�̒��쌠�\�����s�����ǂ����͔C�ӂł��B
>>�@�@�E�{�\�t�g�E�F�A��FAAD2�̃��C�u�����Ńo�C�i�����g�p���Ă��܂��B
>>
>>�@�@�@"Code from FAAD2 is copyright (c) Nero AG, www.nero.com"
>>
>>�@�@�E�r���h�ɕK�v�Ȋ�
>>�@�@�@- Microsoft Visual Studio 2005 �ȏ�@��MFC���K�v
>>�@�@�@- Microsoft Windows SDK v6.0 �ȏ�@�@��DirectShow���N���X�̃R���p�C���ς݃��C�u�������K�v
>>�@�@�@- Microsoft DirectX 9.0 SDK �ȏ�

Special Thanks:
�ESolaris�ŊJ���҂̕�
�E�g���c�[�����̐l
�E��N/E9PqspSk��
�EARIB(�����̖����_�E�����[�h�ɑ΂���)
�E�f�[�^�����p�v���O�����l�ߍ��킹 ����2�̕�(clt2png�̃\�[�X�R�[�h���g�p)

����m�F��:
  Debian GNU/Linux sid
  Linux 2.6.27.19 SMP PREEMPT x86_64

tomy ��CfWlfzSGyg
