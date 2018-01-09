$(function(){

var vueApp = new Vue({
    el: '#vueApp',
    data: {
        input_cmd: '',
        input_port: '',
        process_list: [],
        consolg_messages: []
    }, 
    computed: {

    },
    methods: {
        time2str: function(ts) {
            var dt = new Date();
            dt.setTime(ts * 1000);
            return dt.toLocaleDateString();
        }
    }
});

}); //end function