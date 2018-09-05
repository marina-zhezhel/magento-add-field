define([ 'jquery', 'mage/validation' ], function ($) {
    'use strict';
    $.validator.addMethod('validate-file-size', function (value, element) {
        if ((element.files[0]) && (element.files[0].size / 1024 / 1024 > 3)) {
            return false;
        }
        return true;
    }, $.mage.__('Please upload an image that is smaller than 3MB'));
    $.validator.addMethod('validate-file-type', function (value, element) {
        if (element.files[0]) {
            if (['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/bmp' ].indexOf(element.files[0].type)===-1)
            return false;
        }
        return true;
    }, $.mage.__('Please upload only image that has types .png, .jpeg, .jpg, .gif, .bmp'));
});
