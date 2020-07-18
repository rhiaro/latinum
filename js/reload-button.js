
      var reloadBtn = document.getElementById('reload');
      reloadBtn.addEventListener('click', function(e){
        e.preventDefault();
        var now = new Date();
        var year = now.getFullYear().toString();
        var month = now.getMonth()+1;
        month = month.toString().padStart(2, '0');
        var day = now.getDate();
        day = day.toString().padStart(2, '0');
        var time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
        var zoneDiff = now.getTimezoneOffset() / 60;
        if(zoneDiff <= 0){
          var sign = '+';
        }else{
          var sign = '-';
        }
        var zone = sign + Math.abs(zoneDiff).toString().padStart(2, '0') + ':00';

        var yearEles = document.getElementById('year').getElementsByTagName('option');
        var monthEles = document.getElementById('month').getElementsByTagName('option');
        var dayEles = document.getElementById('day').getElementsByTagName('option');

        for(var i = 0; i < yearEles.length; i=i+1){
          if(yearEles[i].value == year){
            yearEles[i].selected = 'true';
          }
        }
        for(var i = 0; i < monthEles.length; i=i+1){
          if(monthEles[i].value == month){
            monthEles[i].selected = 'true';
          }
        }
        for(var i = 0; i < dayEles.length; i=i+1){
          if(dayEles[i].value == day){
            dayEles[i].selected = 'true';
          }
        }

        document.getElementById('time').value = time;
        document.getElementById('zone').value = zone;

      });
