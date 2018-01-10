

var vueApp = new Vue({
    el: '#vueApp',
    data: {
        input_cmd: '',
        input_port: '',
        process_list: [],
        console_messages: []
    }, 
    computed: {

    },
    methods: {
        time2str: function(ts) {
            var dt = new Date();
            dt.setTime(ts * 1000);
            return dt.toLocaleDateString();
        },
        getClass: function(type) {
            return type == 'fail' ? 'text-danger' : 'text-success';
        },
        startJob: function(){
            var query = [];
            var that = this;
            query.push('action=startJob');
            query.push('cmd=' + this.input_cmd);
            query.push('port=' + this.input_port);
            var url = '/handler.php?' + query.join('&');
            $.get(url, function(res){
                if (res.code == 'fail') {
                    that.console_messags.splice(0, 0, {type: 'fail', text: res.data});
                }
            }, 'json');
        }
    }
});
