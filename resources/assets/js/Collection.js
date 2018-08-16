export default Vue.extend({
    props: ['data'],
    data() {
       return {
           items: this.data
       };
    },
    methods: {
       add(item) {
           this.items.push(item);

           this.$emit('added');
       },
       remove(index) {
           this.items.splice(index, 1);

           this.$emit('removed');
       }
    }
});