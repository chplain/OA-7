$(document).ready(function(){


	if($('#Cc input').val() == ''){
		$('#Cc').hide();
		$('#rmCc').hide();
	}else{
		$('#addCc').hide();
	}
	if($('#Bcc input').val() == ''){
		$('#Bcc').hide();
		$('#rmBcc').hide();
	}else{
		$('#addBcc').hide();
	}
	$('#addCc').click(function(e){
		e.preventDefault();
		$('#Cc').show();
		$('#Cc input').focus();
		$('#addCc').hide();
		$('#rmCc').show();
	});
	$('#addBcc').click(function(e){
		e.preventDefault();
		$('#Bcc').show();
		$('#Bcc input').focus();
		$('#addBcc').hide();
		$('#rmBcc').show();
	});
	$('#rmCc').click(function(e){
		e.preventDefault();
		$('#Cc').hide();
		$('#Cc input').val('');
		$('#rmCc').hide();
		$('#addCc').show();
	});
	$('#rmBcc').click(function(e){
		e.preventDefault();
		$('#Bcc').hide();
		$('#Bcc input').val('');
		$('#rmBcc').hide();
		$('#addBcc').show();
	});


	var inputNum = 1;
	$('input[name=receiver]').blur(function(){
		inputNum = 1;
	});
	$('input[name=Cc]').blur(function(){
		inputNum = 2 ;
	});
	$('input[name=Bcc]').blur(function(){
		inputNum = 3;
	});


	$('#write_right a').click(function(e){
		e.preventDefault();

		if(inputNum == 1){
			var msg = $('input[name=receiver]').val();
			if(msg != '')
				msg += ';';
			msg += $(this).text();
			$('input[name=receiver]').val(msg);
			$('input[name=receiver]').focus();
		}else if(inputNum==2){
			var msg = $('input[name=Cc]').val();
			if(msg != '')
				msg += ';';
			msg += $(this).text();
			$('input[name=Cc]').val(msg);
			$('input[name=Cc]').focus();
		}else if(inputNum==3){
			var msg = $('input[name=Bcc]').val();
			if(msg != '')
				msg += ';';
			msg += $(this).text();
			$('input[name=Bcc]').val(msg);
			$('input[name=Bcc]').focus();
		}
	});


	$('#write_right a span').hide();


	
	

});