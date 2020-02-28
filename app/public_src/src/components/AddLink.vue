<template>
    <b-modal title="Add" id="add-link-modal" @ok="ModalOkHandler">
        <p v-if="ModalData.HighlightedLink.name">Create a new link under {{ ModalData.HighlightedFile.name }}</p>
        <p v-else>Create a new root level link.</p>

        <p>Link name: <input v-model="Link.link_name" type="text" placeholder="some link name" /></p>
        <p>Redirect (optional): <input v-model="Link.link_redirect" type="text" placeholder="http://redirect.to/path" /></p>
    </b-modal>
</template>

<script>
    export default {
        name: "AddLink",
        props: {
            ModalData : Object
        },
        data() {
            return {
                Link : {
                    link_name : '',
                    link_redirect : '',
                }

            };
        },
        methods: {
            ModalOkHandler(bvModalEvent) {
                let url = '/admin/navigation/link'
                let PostData = this.Link
                let self = this;
                this.$http.post(url, PostData).
                    then(function(resp) {
                        console.log(resp);
                        self.$parent.show_toast(resp.data.message);
                    }).catch(function(err) {
                        self.$parent.show_toast(err.response.data.message);
                    }).finally(function(){
                        //self.$parent.get_dir_files(self.ModalData.CurrentDirPath.name);//refresh just in case
                    });
                // let url = '/admin/assets/' + this.ModalData.CurrentDirPath.name + '/' + this.ModalData.HighlightedFile.name;
                // let self = this;
                // this.$http.delete(url).
                // then(function() {
                //     //self.$parent.get_dir_files(self.$parent.CurrentDirPath.name);
                // }).catch(function(err) {
                //     //self.$parent.get_dir_files(self.$parent.CurrentDirPath.name);//refresh just in case
                //     self.$parent.show_toast(err.response.data.message);
                // }).finally(function(){
                //     self.$parent.get_dir_files(self.ModalData.CurrentDirPath.name);//refresh just in case
                // });
            }
        }
    }
</script>

<style scoped>

</style>