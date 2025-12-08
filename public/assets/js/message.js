window.showMessage = function (type, message) {
    var type_style = '';
    var icon = '';
    var title_color = '';

    if (type == 'success') {
        type_style = 'border-green-600';
        title_color = 'text-green-600';
        icon = '<div class="inline-flex items-center bg-green-600 p-2 text-white text-sm rounded-full flex-shrink-0">\
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">\
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />\
                    </svg>\
                </div>';
    }
    if (type == 'warning') {
        type_style = 'border-yellow-600';
        title_color = 'text-yellow-400';
        icon = '<div class="inline-flex items-center bg-yellow-400 p-2 text-white text-sm rounded-full flex-shrink-0">\
                    <svg class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">\
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />\
                    </svg>\
                </div>';
    }
    if (type == 'info') {
        type_style = 'border-blue-600';
        title_color = 'text-blue-600';
        icon = '<div class="inline-flex items-center bg-blue-600 p-2 text-white text-sm rounded-full flex-shrink-0">\
                <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">\
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />\
                    </svg>\
                </div>';
    }
    if (type == 'error') {
        type_style = 'border-red-600';
        title_color = ' text-red-600';
        icon = '<div class="inline-flex items-center bg-red-600 p-2 text-white text-sm rounded-full flex-shrink-0">\
                <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">\
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />\
                    </svg>\
                </div>';
    }

    $('.flasher').html('<div class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto border-l-4 ' + type_style + '">\
        <div class="relative rounded-lg shadow-xs overflow-hidden">\
            <div class="p-4">\
                <div class="flex items-start">\
                    ' + icon + '\
                    <div class="ml-4 w-0 flex-1">\
                        <p class="text-base leading-5 font-medium capitalize ' + title_color + '">\
                            ' + type + '\
                        </p>\
                        <p class="mt-1 text-sm leading-5 text-gray-500">\
                            ' + message + '\
                        </p>\
                    </div>\
                </div>\
            </div>\
        </div>\
    </div>');
    $('.flasher').removeClass('d-none');
    setTimeout(
        function () {
            $('.flasher').addClass('d-none');
        }, 2000);
};