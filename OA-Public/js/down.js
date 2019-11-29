$(document).ready(function(){
	// alert('oWordApp');

	function saveword(){

		var oWordApp = new ActiveXObject('Word.Application');
		var oDocument = oWordApp.Documents.Open("C:\doc2html\x.doc");
		oDocument.SaveAs("C:\doc2html\test.htm",8);
		oWordApp.Quit();
	}

	$('#down').click(function(){
		saveword();
	});

});