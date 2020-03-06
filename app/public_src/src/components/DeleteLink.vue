<template>
    <b-modal title="Delete Link" id="delete-link-modal" @ok="modal_ok_handler" @cancel="modal_cancel_handler" @show="modal_show_handler">
        <div v-if="ModalData.HighlightedLink.link_name">
            <p>Are you sure you want to delete "{{ModalData.HighlightedLink.link_name}}"?</p>
        </div>
        <div v-else>
            <p>There is no link selected!</p>
        </div>
    </b-modal>
</template>

<script>
    export default {
        name: "DeleteLink",
        // props: {
        //     ModalData : Object,
        // },
        props: ['ModalData'],
        methods: {
            modal_ok_handler(bvModalEvent) {
                //let url = '/admin/navigation/link/' + this.ModalData.HighlightedLink.link_uuid;
                let url = '/admin/navigation/link/' + this.ModalData.HighlightedLink.meta_object_uuid;
                let self = this;
                this.$http.delete(url).
                then(function(resp) {
                    this.ModalData.HighlightedLink = {}
                    self.$parent.show_toast(resp.data.message);
                }).catch(function(err) {
                    self.$parent.show_toast(err.response.data.message);
                }).finally(function(){
                    //todo - update the tree without refresh
                    self.$parent.get_navigation_links();
                });
            },
            modal_cancel_handler() {

            },
            modal_show_handler() {

            }
        }
    }
</script>

<style scoped>

</style>