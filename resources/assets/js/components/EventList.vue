<template>
    <div>
        <ul>
            <li v-for="event in events" v-text="event"></li>
        </ul>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                events: []
            }
        },
        created() {
            axios.get('/events').then(response => (this.events = response.data))
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
