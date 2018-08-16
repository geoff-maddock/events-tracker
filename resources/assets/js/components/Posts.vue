<script>
    import Collection from '../Collection';
    import Post from './Post.vue';

    export default Collection.extend({
        components: { Post },
        data() {
            return {
                endpoint: location.pathname + '/posts'
            }
        }
    })
</script>