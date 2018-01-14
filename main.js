

var vueApp = new Vue({
    el: '#vueApp',
    data: {
        input_cmd: '',
        input_port: '',
        process_list: [],
        console_messages: [],
        in_ajax: false
    }, 
    computed: {
        computedSubmitText: function(){
            return (this.in_ajax == false) ? 'Start' : 'Processing...';
        }
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
        startJob: function(){
            if(this.in_ajax == true) return this._log('fail', 'There is a request in processing, please wait...');
            this.in_ajax = true;
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
                that.in_ajax = false;
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
                that._log('success', 'Job list fetched');
            }, 'json');
        },
        _log: function(type, text){
            var current = new Date();
            var h = current.getHours().toString();
            var m = current.getMinutes().toString();
            var s = current.getSeconds().toString();
            var l = [];
            l.push((h.length < 2) ? '0' + h : h);
            l.push((m.length < 2) ? '0' + m : m);
            l.push((s.length < 2) ? '0' + s : s);
            this.console_messages.splice(0, 0, {type: type, text: text, time: l.join(':')});
            if(this.console_messages.length > 100) this.console_messages.splice(100, 1);
        }
    },
    components: {
        'process-row': {
            template: '#process-row',
            props: ['process'],
            data: function(){
                return {
                    cmd: this.process.cmd,
                    name: this.process.name,
                    status: this.process.status,
                    checked_at: this.process.checked_at,
                    port: this.process.port,
                    pid: this.process.pid,
                    now: 0,
                }
            },
            computed: {
                lastCheckedAt: function(){
                    if (this.pid == -1) return '...';
                    var diff = this.now - this.checked_at;
                    if (diff < 0) diff = 0;
                    return diff + ' seconds ago';
                }
            },
            methods: {
                checkJob: function(){
                    if(this.pid == -1) return;
                    var that = this;
                    var query = [];
                    query.push('action=checkJob');
                    query.push('port=' + that.port);
                    var url = '/handler.php?' + query.join('&');
                    $.get(url, function(res){
                        if (res.code == 'fail') {
                            vueApp._log('fail', 'SystemError: ' + res.data);
                        } else if (res.data.status == false) {
                            vueApp._log('fail', 'Job on port:' + that.port + ' is no longer running');
                            that.status = 'stopped';
                            that.pid = -1;
                        }
                        that.checked_at = res.time;
                    }, 'json');
                },
                stopJob: function(){
                    var that = this;
                    var query = [];
                    query.push('action=stopJob');
                    query.push('port=' + that.port);
                    var url = '/handler.php?' + query.join('&');
                    $.get(url, function(res){
                        if (res.code == 'fail') {
                            vueApp._log('fail', 'Fail to kill job with pid: ' + that.pid + ', because: ' + res.data);
                        } else {
                            vueApp._log('success', 'Job with pid: ' + that.pid +  ' has been killed');
                            that.pid = -1;
                            that.status = 'stopped';
                        }
                    }, 'json');
                },
                restartJob: function(){
                    var that = this;
                    var query = [];
                    query.push('action=startJob');
                    query.push('cmd=' + this.cmd);
                    query.push('port=' + this.port);
                    var url = '/handler.php?' + query.join('&');
                    $.get(url, function(res){
                        if (res.code == 'fail') {
                            vueApp._log('fail', res.data);
                        } else {
                            vueApp._log('succes', that.cmd + ': job has been restarted');
                            that.status = 'running';
                            that.pid = res.data.pid;
                            that.checked_at = res.time;
                        }
                        that.in_ajax = false;
                    }, 'json');
                },
                getStatusClass: function(){
                    if (this.status == 'running') {
                        return 'label label-success';
                    } else if (this.status == 'stopped') {
                        return 'label label-danger';
                    }
                },
                getTimestamp: function(){
                    var current = new Date();
                    return parseInt(current.getTime() / 1000);
                }
            },
            mounted: function(){
                var that = this;
                setInterval(function(){
                    that.checkJob();
                }, 7000);
                setInterval(function(){
                    that.now = that.getTimestamp();
                });
            }
        }
    },
    mounted: function(){
        this.fetchJob();
    }
});
