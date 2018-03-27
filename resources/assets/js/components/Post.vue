<script>
    export default {
        props: ['attributes'],
        data() {
            return {
                editing: false,
                body: this.attributes.body
            };
        },
        methods: {
            update() {
                axios.patch('/posts/' + this.attributes.id, {
                    body: this.body
                });
                this.editing = false;
                flash('Updated!');
            },
            destroy() {
                axios.delete('/posts/' + this.attributes.id);
                $(this.$el).fadeOut(300, () => {
                    flash('Your post has been deleted.');
                });
            }
        }
    }
</script>