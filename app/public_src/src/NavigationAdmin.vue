<template>
    <div>
        <div>Navigation Admin</div>

        <ButtonC v-bind:ButtonData="Buttons.AddLinkButton" v-b-modal.add-link-modal></ButtonC>
        <ButtonC v-bind:ButtonData="Buttons.EditLinkButton" v-b-modal.edit-link-modal></ButtonC>
        <ButtonC v-bind:ButtonData="Buttons.DeleteLinkButton" v-b-modal.delete-link-modal></ButtonC>
        <ButtonC v-bind:ButtonData="Buttons.SaveNavigationButton" v-b-modal.generic-modal></ButtonC>

        <div style="clear:both"></div>

        <TreeC :value="TreeData">
            <span slot-scope="{node, index, path, tree}" @click="highlight_link(node)" @dblclick="edit_link_handler(node)" :class="{ highlighted: node.meta_object_uuid === ModalData.HighlightedLink.meta_object_uuid}">
                <!--
                <b>{{index}}</b>
                Title: {{node.link_name}}
                - path: <i>{{path.join(',')}}</i>
                -->
                {{index}}. <strong>{{node.link_name}}</strong> ({{node.link_type_description}})
                <!-- ( {{path.join(' => ')}} ) -->
            </span>
        </TreeC>

        <!-- modals -->
        <AddLinkC v-bind:ModalData="ModalData"></AddLinkC>
        <EditLinkC v-bind:ModalData="ModalData"></EditLinkC>
        <DeleteLinkC v-bind:ModalData="ModalData"></DeleteLinkC>
        <SaveNavigationC v-bind:ModalData="SaveNavigationModalData"></SaveNavigationC>
    </div>


</template>

<script>
    // https://he-tree-vue.phphe.com/
    // https://github.com/phphe/he-tree-vue

    import {
        Tree, // Base tree
        Fold, Check, Draggable, // plugins
        cloneTreeData, walkTreeData, getPureTreeData, // utils
    } from 'he-tree-vue'

    import 'he-tree-vue/dist/he-tree-vue.css' // base style

    import ButtonC from '@GuzabaPlatform.Platform/components/Button.vue'

    import AddLinkC from '@GuzabaPlatform.Navigation/components/AddLink.vue'
    import EditLinkC from '@GuzabaPlatform.Navigation/components/EditLink.vue'
    import DeleteLinkC from '@GuzabaPlatform.Navigation/components/DeleteLink.vue'
    import SaveNavigationC from '@GuzabaPlatform.Platform/components/GenericModal.vue'

    import ToastMixin from '@GuzabaPlatform.Platform/ToastMixin.js'

    export default {
        name: "NavigationAdmin",
        mixins: [
            ToastMixin,
        ],
        components: {
            TreeC: Tree.mixPlugins([Draggable]),
            ButtonC,
            AddLinkC,
            EditLinkC,
            DeleteLinkC,
            SaveNavigationC,
        },
        data() {
            return {
                // TreeData: [
                //     {
                //         text: 'node 1'
                //     },
                //     {
                //         text: 'node 2', children: [{text: 'node 2-1'}]
                //     }
                // ]
                TreeData: [],
                // HighlightedLink: {
                //     link_uuid: '',
                //     link_name: '',
                //     link_redirect: '',
                // },
                ModalData: {
                    HighlightedLink: {
                        // link_uuid: '',
                        // link_name: '',
                        // link_redirect: '',
                    },
                },
                SaveNavigationModalData: {
                    title : 'Save Navigation Order',
                    text : 'Confirm saving Navigation?',
                    action_url : '/admin/navigation',
                    method: 'patch',
                    AdditionalData: {
                        links: {}
                    }
                },
                Buttons: {
                    AddLinkButton: {
                        label: 'Add Link',
                        is_active: true,
                        handler: this.blank_button_handler,
                    },
                    EditLinkButton: {
                        label: 'Edit Link',
                        is_active: true,
                        handler: this.blank_button_handler,
                    },
                    DeleteLinkButton: {
                        label: 'Delete Link',
                        is_active: true,
                        handler: this.blank_button_handler,
                    },
                    SaveNavigationButton: {
                        label: 'Save Navigation',
                        is_active: true,
                        handler: this.blank_button_handler,
                    },
                }
            }
        },
        watch : {
            TreeData: function() {
                this.SaveNavigationModalData.AdditionalData.links = this.TreeData;
            }
        },
        methods: {
            blank_button_handler() { //to be used when the button is handled by a modal dialog

            },
            edit_link_handler(node) {

                this.highlight_link(node);
                this.$bvModal.show('edit-link-modal');
            },
            highlight_link(node) {
                if (node.meta_object_uuid === this.ModalData.HighlightedLink.meta_object_uuid) {
                    //unhihglight the link
                    //this.ModalData.HighlightedLink.link_uuid = ''
                    //this.ModalData.HighlightedLink.link_name = ''
                    //this.ModalData.HighlightedLink.link_redirect = ''
                    this.ModalData.HighlightedLink = Object.keys(node);
                } else {
                    //this.HighlightedLink.link_uuid = node.meta_object_uuid
                    //this.HighlightedLink.link_name = node.link_name
                    //this.HighlightedLink.link_redirect = node.link_redirect
                    this.ModalData.HighlightedLink = node
                }

            },
            get_navigation_links() {
                let url = '/admin/navigation'
                this.$http.get(url).
                    then( resp => {
                        this.TreeData = Object.values(resp.data.links)
                    });
            }
        },
        mounted() {
            //this.ModalData.HighlightedLink = this.HighlightedLink;//no longer needed
            this.SaveNavigationModalData.AdditionalData = {};
            //this.SaveNavigationModalData.AdditionalData.links = this.TreeData;
            this.SaveNavigationModalData.AdditionalData.links = this.TreeData;//this is not working as expected (AdditionalData.links does not contain the data)...
            //perhaps the component overwrites the whole object...
            //an explicit watcher is added

            this.get_navigation_links()
        }
    }
</script>

<style scoped>
    .highlighted
    {
        background-color: deepskyblue;
    }
    .tree-node
    {
        border: 1px solid red !important;
        border-radius: 5px;
    }

</style>