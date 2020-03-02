<template>
    <b-modal title="Add Link" id="add-link-modal" @ok="modal_ok_handler" @cancel="modal_cancel_handler" @show="modal_show_handler">
        <p v-if="ModalData.HighlightedLink.link_name">Create a new link under "{{ ModalData.HighlightedLink.link_name }}".</p>
        <p v-else>Create a new root level link.</p>
        <p>Link name: <input v-model="Link.link_name" type="text" placeholder="some link name" /></p>
        <b-tabs content-class="mt-3">
            <b-tab title="Holder Link" active>
                <p>The holder links are used for defining the structure of the navigation.</p>
            </b-tab>
            <b-tab title="Redirect">
                <p>Redirect: <input v-model="Link.link_redirect" type="text" placeholder="http://redirect.to/path" /></p>
            </b-tab>
            <b-tab title="To Controller">
                <p>Controller Class: <v-select v-model="Link.link_class_name" :options="ControllerClasses"></v-select></p>
                <p>Action: <v-select v-model="Link.link_class_action" :options="ControllerActions"></v-select></p>
            </b-tab>
            <b-tab title="To Object">
                <p>Class: <v-select :options="Classes"></v-select></p>
                <p>Object: <v-select :options="Objects"></v-select></p>
            </b-tab>
        </b-tabs>
    </b-modal>
</template>

<script>

    import vSelect from 'vue-select'
    import 'vue-select/dist/vue-select.css'
    //@import "vue-select/src/scss/vue-select.scss"

    export default {
        name: "AddLink",
        components: {
            vSelect
        },
        props: {
            ModalData : Object
        },
        data() {
            return {
                Link : {},
                LinkBlank : {
                    link_name : '',
                    link_redirect : '',
                    //parent_link_id: null,
                    parent_link_uuid: null,
                    link_class_name: '',
                    link_class_action: '',
                },
                Classes: [],
                Objects: [],
                ControllerClasses: [],
                ControllerActions: [],
            };
        },
        methods: {
            modal_ok_handler(bvModalEvent) {
                let url = '/admin/navigation/link'
                let PostData = this.Link
                let self = this;
                this.Link = JSON.parse(JSON.stringify(this.LinkBlank));//copy the object
                this.$http.post(url, PostData).
                    then(function(resp) {
                        console.log(resp);
                        self.$parent.show_toast(resp.data.message);
                    }).catch(function(err) {
                        self.$parent.show_toast(err.response.data.message);
                    }).finally(function(){
                        //todo - update the tree without refresh
                        self.$parent.get_navigation_links();
                    });
            },
            modal_cancel_handler() {
                this.Link = JSON.parse(JSON.stringify(this.LinkBlank));//copy the object
            },
            modal_show_handler() {
                this.Link = JSON.parse(JSON.stringify(this.LinkBlank));//copy the object
                //this.Link.parent_link_id = this.ModalData.HighlightedLink.link_id
                this.Link.parent_link_uuid = this.ModalData.HighlightedLink.meta_object_uuid
                //console.log(this.Link);
                this.load_controller_classes();

            },
            load_controller_classes() {
                let url = '/base/controllers'
                let self = this
                this.$http.get(url).
                    then(function(resp) {
                        self.ControllerClasses = resp.data.controllers
                    }).catch(function(err) {
                        self.$parent.show_toast(err.response.data.message)
                    })
            },
            load_controller_actions() {
                if (this.Link.link_class_name) {
                    let url = '/base/actions/' + this.Link.link_class_name.split('\\').join('-')
                    let self = this
                    this.$http.get(url).
                    then(function(resp) {
                        self.ControllerActions = resp.data.actions
                    }).catch(function(err){
                        self.$parent.show_toast(err.response.data.message)
                    })
                } else {
                    this.Link.link_class_action = ''
                }


            },
            load_classes() {

            },
            load_objects() {

            }
        },
        watch: {
            // Link(Link) {
            //     console.log(Link)
            // }
            Link: {
                handler(Link) {
                    //if (Link.link_class_name) {
                    this.load_controller_actions()
                    //}
                },
                deep: true
            }
        }
    }
</script>

<style scoped>

</style>