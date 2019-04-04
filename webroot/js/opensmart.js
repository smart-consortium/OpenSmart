/*
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright   Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link        https://smart-consortium.org OpenSmart Project
 * @since       1.0.0
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
$(function () {

    $(document).ready(function () {
        var query = location.search
        if (query.indexOf('view=all') >= 0) {
            $('#view_all').prop('checked', true);
        }
    });


    $('#view_all').click(function () {
        if ($(this).prop('checked')) {
            location.href = '?view=all';
        } else {
            location.href = '/';
        }
    });

});