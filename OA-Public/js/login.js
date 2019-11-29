$(document).ready(function(){
	$('#unclear').click(function(event){
		event.preventDefault();
		$('#codeimg').attr('src','/OA/index.php/Index/Login/verify.html?r='+Math.random());
	});
});