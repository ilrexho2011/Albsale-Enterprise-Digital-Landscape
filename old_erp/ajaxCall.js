
// POST REQUEST

$(document).ready(function() {
    $('#postMessage').click(function(e) {
        e.preventDefault();

        // Serialize form data
        var formData = $('form').serializeArray();
        var formObject = {};

        // Convert serialized data to an object
        formData.forEach(function(item) {
            formObject[item.name] = decodeURIComponent(item.value.replace(/\+/g, ' '));
        });

        // Post with AJAX
        $.ajax({
            type: "POST",
            url: "/salt/api/article/create.php",
            data: JSON.stringify(formObject),
            contentType: "application/json", // Corrected header name
            success: function() {
                alert('Successfully posted');
            },
            error: function() {
                alert('Could not be posted');
            }
        });
    });
});
    

//GET REQUEST

  document.addEventListener('DOMContentLoaded',function(){
  document.getElementById('getMessage').onclick=function(){
       
       var req;
       req=new XMLHttpRequest();
       req.open("GET", '/salt/api/user/read.php',true);
       req.send();
      
       req.onload=function(){
       var json=JSON.parse(req.responseText);

       //limit data called
       var son = json.filter(function(val) {
              return (val.id >= 0);  
          });

      var html = "";

      //loop and display data
      son.forEach(function(val) {
          var keys = Object.keys(val);

          html += "<div class = 'cat'>";
              keys.forEach(function(key) {
              html += "<strong>" + key + "</strong>: " + val[key] + "<br>";
              });
          html += "</div><br>";
      });

      //append in message class
      document.getElementsByClassName('message box')[0].innerHTML=html;         
      };
    };
  });