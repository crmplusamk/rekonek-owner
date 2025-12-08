$('select[name=province]').on('change', function(e){
  var id = $(this).val();
  get_city(id);
  // refresh district
  $('select[name=district]').empty();
  $('select[name=district]').append('<option value="">Pilih Kecamatan</option>');
});

$('select[name=city]').on('change', function(e){
  var id = $(this).val();
  get_district(id);
});

function get_city(province_id)
{
  $.ajax({
    url: "/ajax/get_cities",
    method: "get",
    data: { province_id: province_id},
    success: function(data){
      $('select[name=city]').empty();
      $.each(data, function (index, value) {
        $('select[name=city]').append('<option value="'+ value.id +'">'+ value.type + ' ' + value.name +'</option>');
      });
      $('select[name=city]').attr('disabled',false);
    }
  });
}

function get_selected_city(province_id, city_id)
{
  $.ajax({
    url: "/ajax/get_cities",
    method: "get",
    data: { province_id: province_id},
    success: function(data){
      $('select[name=city]').empty();
      $.each(data, function (index, value) {
        if(value.id == city_id){
          $('select[name=city]').append('<option value="'+ value.id +'" selected>'+ value.type + ' ' + value.name +'</option>');
        }else{
          $('select[name=city]').append('<option value="'+ value.id +'">'+ value.type + ' ' + value.name +'</option>');
        }
      });
    }
  });
}

function get_district(city_id)
{
  $.ajax({
    url: "/ajax/get_districts",
    method: "get",
    data: { city_id: city_id},
    success: function(data){
      $('select[name=district]').empty();
      $.each(data, function (index, value) {
        $('select[name=district]').append('<option value="'+ value.id +'">'+ value.name +'</option>');
      });
      $('select[name=district]').attr('disabled',false);
    }
  });
}

function get_selected_district(city_id, district_id)
{
  $.ajax({
    url: "/ajax/get_districts",
    method: "get",
    data: { city_id: city_id},
    success: function(data){
      $('select[name=district]').empty();
      $.each(data, function (index, value) {
        if(value.id == district_id){
          $('select[name=district]').append('<option value="'+ value.id +'" selected>'+ value.name +'</option>');
        }else{
          $('select[name=district]').append('<option value="'+ value.id +'">'+ value.name +'</option>');
        }
      });
    }
  });
}