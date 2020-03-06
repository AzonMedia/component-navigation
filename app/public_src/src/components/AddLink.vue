<template>
    <b-modal no-close-on-backdrop title="Add Link" id="add-link-modal" @ok="modal_ok_handler" @cancel="modal_cancel_handler" @show="modal_show_handler">
        <p v-if="ModalData.HighlightedLink.link_name">Create a new link under "{{ ModalData.HighlightedLink.link_name }}".</p>
        <p v-else>Create a new root level link.</p>
        <p>Link name: <input v-model="Link.link_name" type="text" placeholder="some link name" /></p>

        <b-tabs content-class="mt-3" v-model="active_tab">
            <b-tab title="Holder Link" active  @click="tab_handler">
                <p>The holder links are used for defining the structure of the navigation.</p>
            </b-tab>
            <b-tab title="Redirect" @click="tab_handler">
                <p>Redirect: <input v-model="Link.link_redirect" type="text" placeholder="http://redirect.to/path or select" /></p>
                <p><v-select v-model="Link.link_redirect" :options="FrontendRoutes" placeholder="http://redirect.to/path or select from dropdown"></v-select></p>
            </b-tab>
            <!-- the below are server side routes -->

            <b-tab title="To Controller" @click="tab_handler">
                <p>Controller Class: <v-select v-model="Link.link_class_name" :options="ControllerClasses"></v-select></p>
                <p>Action: <v-select v-model="Link.link_class_action" :options="ControllerActions"></v-select></p>
            </b-tab>
            <b-tab title="To Object" @click="tab_handler">
                <p>Class: <v-select v-model="Link.link_class_name" :options="ModelClasses"></v-select></p>
                <p>Object: <v-select v-model="Link.link_object_uuid" :options="ModelObjects"></v-select></p>
            </b-tab>

        </b-tabs>

        <!--
        <p>Link to: <input v-model="Link.link_redirect" type="text" placeholder="http://redirect.to/path or select" /></p>
        <p><v-select v-model="Link.link_redirect" :options="FrontendRoutes" placeholder="http://redirect.to/path or select from dropdown"></v-select></p>
        -->
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
                    link_class_name: '',//both the Controller tab and Object tab use this
                    link_class_action: '',
                    link_object_uuid: '',
                },
                FrontendRoutes: [],
                ModelClasses: [],
                ModelObjects: [],
                ControllerClasses: [],
                ControllerActions: [],
                active_tab: 0,
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
                this.load_controller_classes();
                this.load_model_classes();
                this.load_frontend_routes();
            },

            load_frontend_routes() {
                let url = '/admin/navigation/frontend-routes'
                let self = this
                this.$http.get(url).
                then(function(resp) {
                    //self.StaticContent = resp.data.content
                    //vue-select currently does not support optgroups https://github.com/sagalbot/vue-select/issues/342
                    //so instead a holder element will be inserted
                    let FrontendRoutes = [];
                    for (const el in resp.data) {
                        //FrontendRoutes.push('');
                        FrontendRoutes.push('### ' + el.toUpperCase() + ' ###');
                        //FrontendRoutes.push('');
                        console.log(el);
                        console.log(resp.data[el]);
                        for(const el2 in resp.data[el]) {
                            FrontendRoutes.push(resp.data[el][el2]);
                        }
                    }
                    self.FrontendRoutes = FrontendRoutes;
                }).catch(function(err) {
                    self.$parent.show_toast(err.response.data.message)
                })
            },

            load_model_classes() {
                let url = '/base/models'
                let self = this
                this.$http.get(url).
                    then(function(resp) {
                        self.ModelClasses = resp.data.models
                    }).catch(function(err) {
                        self.$parent.show_toast(err.response.data.message)
                    })
            },
            load_model_objects() {
                if (this.Link.link_class_name) {
                    let url = '/base/models/' + this.Link.link_class_name.split('\\').join('-')
                    let self = this
                    this.$http.get(url).
                    then(function(resp) {
                        self.ModelObjects = resp.data.objects
                    }).catch(function(err){
                        self.$parent.show_toast(err.response.data.message)
                    })
                } else {
                    this.Link.link_object_uuid = ''
                }
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
                    let url = '/base/controllers/' + this.Link.link_class_name.split('\\').join('-')
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

            tab_handler() {
                // this.ControllerClasses = []
                // this.ControllerActions = []
                // this.ModelClasses = []
                // this.ModelObjects = []
            },
        },
        watch: {
            // Link(Link) {
            //     console.log(Link)
            // }
            // Link: {
            //     handler() {
            //         this.load_controller_actions()
            //     },
            //     deep: true
            // }
            //watch just the needed properties
            'Link.link_class_name' : function() {
                if (this.active_tab === 2) {
                    this.load_controller_actions()
                } else if (this.active_tab === 3) {
                    this.load_model_objects()
                }
            }
        }
    }
</script>

<style scoped>
.add-link-modal {
    height: 500px;
    border: 1px solid red;
}
</style>