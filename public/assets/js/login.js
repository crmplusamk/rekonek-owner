$('#username').keyup(function ()
{
    if ($(this).val() != "" && $('#password').val() != "") {
        $('#captcha').show();
        $('#captcha-input').show();
    } else {
        $('#captcha').hide();
        $('#captcha-input').hide();
        $('#captcha-input').val('');
    }
});

$('#password').keyup(function ()
{
    if ($(this).val() != "" && $('#username').val() != "") {
        $('#captcha').show();
        $('#captcha-input').show();
    } else {
        $('#captcha').hide();
        $('#captcha-input').hide();
        $('#captcha-input').val('');
    }
});

$(".show_hide_password").each(function ()
{
    var container = $(this);
    var link = $(this).find('a');

    link.on('click', function () {
        event.preventDefault();
        var text = $(container).find('input');
        var icon = $(container).find('i');

        if (text.attr("type") == "text") {
            text.attr('type', 'password');
            icon.removeClass("mdi-eye");
            icon.addClass("mdi-eye-off");

        } else if (text.attr("type") == "password") {
            text.attr('type', 'text');
            icon.removeClass("mdi-eye-off");
            icon.addClass("mdi-eye");
        }
    });
});
