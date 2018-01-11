

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
            return dt.toLocaleString();
        },
        getClass: function(type) {
            return type == 'fail' ? 'text-danger' : 'text-success';
        },
        statusLabel: function(status) {
            if (status == 'running') {
                return 'label label-success';
            } else if (status == 'checking') {
                return 'label label-warning';
            } else if (status == 'stopped') {
                return 'label label-danger';
            }
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
                    that._log('fail', res.data);
                } else {
                    that._log('succes', that.input_cmd + ': job started');
                    var process = res.data;
                    process.status = 'running';
                    process.checked_at = res.time;
                    that.process_list.push(process);
                    that.input_cmd = '';
                    that.input_port =  '';
                }
            }, 'json');
        },
        checkJob: function(index){
            this.process_list[index].status = 'checking';
            var process = this.process_list[index];
            var query = [];
            query.push('action=checkJob');
            query.push('port=' + process.port);
            var url = '/handler.php?' + query.join('&');
            var that = this;
            $.get(url, function(res){
                if (res.code == 'fail') {
                    that._log('fail', 'Job on port:' + process.port + ' is no longer running');
                    that.process_list[index].status = 'stopped';
                } else {
                    that._log('success', 'Job on port:' + process.port + ' is still running');
                    that.process_list[index].status = 'running';
                }
            }, 'json');
        },
        fetchJob: function(){
            var query = [];
            var url = '/handler.php?action=fetchJob';
            var that = this;
            $.get(url, function(res){
                var ts = res.time;
                res.data.forEach(function(process){
                    process.status = 'running';
                    process.checked_at = ts;
                    that.process_list.push(process);
                });
            }, 'json');
        },
        _log: function(type, text){
            this.console_messages.splice(0, 0, {type: type, text: text});
        }
    },
    mounted: function(){
        var that = this;
        that.fetchJob();
        setInterval(function(){
            if (that.process_list.length == 0) return;
            that.process_list.forEach(function(item, index){
                that.checkJob(index);
            });
        }, 5000);
    }
});
