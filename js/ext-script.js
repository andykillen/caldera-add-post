jQuery(document).ready(function($){
if($("form.caldera_forms_form .add-editor textarea").length){
    $("form.caldera_forms_form .add-editor textarea").addClass('tinymce-enabled');
    tinyMCE.init({
            menubar:false,
            mode : "specific_textareas",
            theme : "modern", 
            plugins : "image",
            editor_selector :"tinymce-enabled"
        });                
}



});

function caldera_extension_submission_cb(obj){
    console.log(obj.data);
}