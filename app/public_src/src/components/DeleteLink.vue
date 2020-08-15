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
                this.$http.delete(url).
                    then(resp => {
                        this.ModalData.HighlightedLink = {}
                        this.$parent.show_toast(resp.data.message);
                    }).catch(err => {
                        this.$parent.show_toast(err.response.data.message);
                    }).finally(() => {
                        //todo - update the tree without refresh
                        this.$parent.get_navigation_links();
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