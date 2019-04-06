//分
var execute_min = [];
for (i = 0; i < 60; i++) {
    var execute_min_obj = {};
    execute_min_obj.id = i < 10 ? '0' + i : i;
    execute_min_obj.value = i < 10 ? '0' + i + '分' : i + '分';
    execute_min.push(execute_min_obj);
}
//秒
var execute_sec = [];
for (i = 0; i < 60; i++) {
    var execute_sec_obj = {};
    execute_sec_obj.id = i < 10 ? '0' + i : i;
    execute_sec_obj.value = i < 10 ? '0' + i + '秒' : i + '秒';
    execute_sec.push(execute_sec_obj);
}
//次
var execute_num = [];
for (i = 1; i <= 60; i++) {
    var execute_num_obj = {};
    execute_num_obj.id = i;
    execute_num_obj.value = i;
    execute_num.push(execute_num_obj);
}