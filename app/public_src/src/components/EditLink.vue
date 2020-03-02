<template>
    <b-modal title="Edit Link" id="edit-link-modal" @ok="modal_ok_handler" @cancel="modal_cancel_handler" @show="modal_show_handler">
        <div v-if="ModalData.HighlightedLink.link_name">
            <p>Link name: <input v-model="ModalData.HighlightedLink.link_name" type="text"></p>
            <!--
            <p>Redirect (optional): <input v-model="ModalData.HighlightedLink.link_redirect" type="text" /></p>
            <p>Class (optional): <input v-model="ModalData.HighlightedLink.link_class_name" type="text" /></p>
            <p>Class Action (optional): <input v-model="ModalData.HighlightedLink.link_class_action" type="text" /></p>
            <p>Object Id (optional): <input v-model="ModalData.HighlightedLink.link_object_uuid" type="text" /></p>
            -->
        </div>
        <div v-else>
            <p>There is no link selected!</p>
        </div>
    </b-modal>
</template>

<script>

    //todo - allow all properties to be edited like on Add link
    //load the tab that has data

    export default {
        name: "EditLink",
        // props: {
        //     ModalData : Object,
        // },
        props: ['ModalData', 'OriginalModalData'],
        methods: {
            modal_ok_handler(bvModalEvent) {
                //let url = '/admin/navigation/link/' + this.ModalData.HighlightedLink.link_uuid;
                let url = '/admin/navigation/link/' + this.ModalData.HighlightedLink.meta_object_uuid;
                //let PostData = this.ModalData.HighlightedLink
                let PostData = {};
                PostData.link_name = this.ModalData.HighlightedLink.link_name
                PostData.link_redirect = this.ModalData.HighlightedLink.link_redirect
                let self = this;
                this.$http.patch(url, PostData).
                then(function(resp) {
                    console.log(resp);
                    self.$parent.show_toast(resp.data.message);
                }).catch(function(err) {
                    self.$parent.show_toast(err.response.data.message);
                }).finally(function(){
                    //do not refresh as this may loose other unsaved changes
                    //self.$parent.get_navigation_links();//refresh just in case
                });
            },
            modal_cancel_handler() {
                //this.ModalData = JSON.parse(JSON.stringify(this.OriginalModelData)) //deep clone and produce again Array
                //this.ModalData.HighlightedLink.link_name = 'fff';
                for (const el in this.OriginalModelData.HighlightedLink) {
                    this.ModalData.HighlightedLink[el] = this.OriginalModelData.HighlightedLink[el];
                }
            },
            modal_show_handler() {
                this.OriginalModelData = JSON.parse(JSON.stringify(this.ModalData)) //deep clone and produce again Array
            }
        }
    }
</script>

<style scoped>

</style>