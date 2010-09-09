function confirm_delete(url) {
    var answer = confirm ("Are you sure?")
    if (answer)
        window.location=url;
}

function dummy() {
	return false;
}

$(document).ready(function() {
	$('#myform').submit(function() {
	  //alert($(this).serialize());
	  for(var i=0;i<editor1.nicInstances.length;i++){editor1.nicInstances[i].saveContent();}
	  //alert($("#posts").val());
	  $.ajax({
		  type: "POST",
		  url: 'index.php',
		  dataType: 'json',
		  data: $(this).serialize(),
		  beforeSend: function(msg){
			 $("#myform").append('<img class="loader" src="'+blogPath+'/images/ajax-loader.gif" />');
		   },
		  success: function(msg){
		     //alert(msg.status);
			 if (msg.status == "error") header = "Error"; 
			 else header = "Success";
			 $.jGrowl(msg.out, { header: header });
			 $('.loader').remove();
			 if ( (msg.func == "newentry") && (msg.status == "success") ) window.location.replace(blogPath);
			 if ( (msg.func == "addcomment") && (msg.status == "success") ) window.location.reload();
			 return false;
		   },
		   error: function(xhr) {
			  //alert("Error "+xhr.statusText);
		   }
		});
		return false;
	});
});
