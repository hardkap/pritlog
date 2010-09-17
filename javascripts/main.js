var t;
var oldtitle = "";
var oldcontent = "";
var oldcategory = "";
var title = "";
var content = "";
var category = "";

var mychanged = false;
var subchanged = false;

function confirm_delete(msgsure, url) {
    var answer = confirm (msgsure)
    if (answer)
        window.location=url;
}

function dummy() {
	return false;
}

$(document).ready(function() {
	$('#myform').submit(function() {
	  //alert($(this).serialize());
	  if (typeof(editor1.nicInstances) != 'undefined')
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
			 else { header = "Success"; subchanged = true; }
			 $.jGrowl(msg.out, { header: header });
			 $('.loader').remove();
			 if ( (msg.func == "newentry") && (msg.status == "success") ) window.location.replace(blogPath);
			 if ( (msg.func == "addcomment") && (msg.status == "success") ) window.location.reload();
		   },
		   error: function(xhr) {
			  //alert("Error "+xhr.statusText);
		   }
		});
		//alert(msg.status);
		return false;
	});
	
	
});

window.onbeforeunload = confirmExit;
function confirmExit()
{
	var s = changed();
	if ((typeof(unsavedChanges) !== 'undefined') && mychanged && !subchanged) {
		return unsavedChanges;
	}
}

var t;
var oldtitle = "";
var oldcontent = "";
var oldcategory = "";

if((typeof(jgrowl) !== 'undefined')) {
	$.jGrowl(jgrowl);
}	

if((typeof(autosave_frequency) !== 'undefined')) {
	autosave();
}
else { clearTimeout(t); }	

function changed() {
	if (typeof(editor1.nicInstances) != 'undefined')
		for(var i=0;i<editor1.nicInstances.length;i++){editor1.nicInstances[i].saveContent();}
		
    title = $("#title").val(); 
    content = $("#posts").val(); 
	category = $("#category").val(); 
	if ((typeof(title) != 'undefined') && (typeof(content) != 'undefined') && (typeof(category) != 'undefined')) {
		if (((title.length > 0) || (content.length > 4) || (category.length > 0)) && (title != oldtitle || content != oldcontent || category != oldcategory)) {
			mychanged = true;
			return true;
		}	
		else
			return false;
	}	
}

function autosave() 
{ 
    t = setTimeout("autosave()", autosave_frequency); 

	if (changed())
    { 
        $.ajax( 
        { 
			type: 'POST',
		    url: 'index.php',
		    dataType: 'json',
			data: $('#myform').serialize(),
            cache: false, 
			beforeSend: function(msg){
				$("#mytimestamp").empty().append('<em id="timestamp">'+autosaving+'</em>');
		    },
            success: function(message) 
            { 
				//alert(message.status);
				if(message !== null)  {
					if (message.status == "success")
						$("#mytimestamp").empty().append('<em id="timestamp">'+autosave_success+message.timestamp+'</em>'); 
					else
						$("#mytimestamp").empty().append('<em id="timestamp">'+autosave_error+'</em>'); 
				}	
				oldtitle = title;
				oldcontent = content;
				oldcategory = category;		
				mychanged = false;
            },
			error: function(xhr) {
			    //alert("Error "+xhr.statusText);
				$("#mytimestamp").empty().append(xhr.statusText);
		    }
        }); 
    } 
}
