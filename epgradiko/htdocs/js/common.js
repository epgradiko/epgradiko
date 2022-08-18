function p_top(){
	if ($(this).scrollTop() > 100) {
			$('#pagetop').fadeIn();
		} else {
			$('#pagetop').fadeOut();
	}
}

function naver_margin(){
	if( $('#naver').is('*') ){
		var new_heigth = $('#naver').height() + 8;
		$('#nav_margin').css( { 'margin-top': new_heigth } );
	}
}

function popJump(selOBJ)
{
	var n = selOBJ.selectedIndex;
	location.href = selOBJ.options[n].value;
}

$(function(){
	$("#pagetop img").draggable({
		stop: function(event,ui){
			return;
		}
	});
	// pagetop
	$("#pagetop img").click(function(){
		scrollTo($(window).scrollLeft(),0);
	});
});

$(window).ready(function(){
	naver_margin();
});

$(window).scroll(function () {
	p_top();
});

$(window).resize(function(){
	naver_margin();
	p_top();
});
