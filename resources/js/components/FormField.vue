<template>
  <DefaultField :field="field" :errors="errors" :show-help-text="showHelpText" :full-width-content="fullWidthContent">
    <template #field>
      <div class="block mb-2 " v-if="selectedResource?.display && selectedResource.display.length">{{ selectedResource.display }}</div>
      <div class="block help-text help-text mb-2" v-else>Nothing selected</div>

      <DefaultButton component="button" size="xs" type="button" dusk="confirm-action-button" class="ml-auto"
        @click="openResourcesModal">
        <span v-if="selectedResource?.display">Change</span>
        <span v-else>Select {{ field.name }}</span>
      </DefaultButton>


      <Modal :show="showResourcesTableModal" @close-via-escape="showResourcesTableModal = false"
        data-testid="confirm-action-modal" tabindex="-1" role="dialog" :size="'5xl'" :modal-style="'window'">
        <form ref="theForm" autocomplete="off" @change="onUpdateFormStatus" @submit.prevent.stop="$emit('confirm')"
          :data-form-unique-id="formUniqueId"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden space-y-6">
          <div>
            <ModalHeader v-text="`Linked ${field.name}`" />

            <div class="p-4">

              <!-- Table filters -->

              <div class="items-end grid grid-cols-4 gap-4 mb-4">
                <SearchSearchInput debounce="100" @input="search = $event" class="w-full">
                </SearchSearchInput>


                <div class="relative w-full " v-for="filter in availableFilters">
                  <span>{{ filter.label }}</span>
                  <SelectControl v-model:selected="filter.value" :options="filter.options"
                    @change="filterChanged(filter, $event)" size="sm" />
                </div>
              </div>

              <!-- End of Table filters -->

              <div class=" overflow-x-auto relative max-h-96 block">
                <table v-if="resources.length > 0" class="w-full divide-y divide-gray-100 dark:divide-gray-700"
                  data-testid="resource-table">
                  <ResourceTableHeader :resource-name="resourceName" :fields="resources[0].fields" class="sticky top-0"
                    :should-show-column-borders="shouldShowColumnBorders" :should-show-checkboxes="true"
                    :sortable="sortable" @order="requestOrderByChange" @reset-order-by="resetOrderBy" />
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto" style="min-height: 24rem;">
                    
                    <ModalResourceTableRow v-for="(resource, index) in filteredResources"
                      :testId="`${resourceName}-items-${index}`"
                      :radio-button-name="field.belongsToRelationship"
                      :checked="resource.id.value == selectedResourceId"
                      @click="selectResource(resource)"

                      :key="`${resource.id.value}-items-${index}`" 
                      :resource="resource" 
                      :resource-name="resourceName"
                      :should-show-checkboxes="true"
                      :click-action="'ignore'" />
                      
                  </tbody>
                </table>
                <div v-if="filteredResources.length == 0" class="w-full text-center p-4">
                  No results found.
                </div>
              </div>
            </div>
          </div>

          <ModalFooter>
            <div class="flex items-center ml-auto">
              <DefaultButton component="button" type="button" dusk="confirm-action-button" class="ml-auto"
                @click="showResourcesTableModal = false">Done</DefaultButton>
            </div>
          </ModalFooter>
        </form>
      </Modal>

      <div class="space-y-4">

        <div v-if="value.length > 0">
          <TagList v-if="field.style === 'list'" :tags="value" @tag-removed="i => removeResource(i)"
            :resource-name="field.resourceName" :editable="!currentlyIsReadonly" :with-preview="field.withPreview" />

          <TagGroup v-if="field.style === 'group'" :tags="value" @tag-removed="i => removeResource(i)"
            :resource-name="field.resourceName" :editable="!currentlyIsReadonly" :with-preview="field.withPreview" />
        </div>
      </div>


    </template>
  </DefaultField>
</template>

<script>
// import { FormField, HandlesValidationErrors } from 'laravel-nova'
import ModalResourceTableRow from './ModalResourceTableRow.vue';

export default {
  name: 'FilterableBelongsToFormField',
  // mixins: [FormField, HandlesValidationErrors],
  components: {ModalResourceTableRow},
  props: ['resourceName', 'resourceId', 'field'],



  data: () => ({
    showResourcesTableModal: false,
    resources: [],
    availableFilters: [],
    value: [],




    initializingWithExistingResource: false,
    createdViaRelationModal: false,
    selectedResource: null,
    selectedResourceId: null,
    softDeletes: false,
    withTrashed: false,
    search: '',
    relationModalOpen: false,
  }),

  methods: {
    initializeComponent() {
      this.withTrashed = false
      this.selectedResourceId = this.currentField?.belongsToId
      
      this.field.fill = this.fill
      this.selectedResource = {
        value: this.selectedResourceId,
        display: this.value
      }
    },
    selectResource(resource) {
      this.selectedResource = {
        value: resource.id.value,
        display: resource.title,
      };
      this.selectedResourceId = resource.id.value;
    },
    fetchResources() {
      Nova.request().get(`/nova-api/${this.field.resourceName}?perPage=1000`)
        .then((data) => {
          this.resources = data.data.resources;
          this.resources.forEach(resource => {
            resource.fields = resource.fields.filter(field => !this.field.hiddenFields.includes(field.attribute))
          })
        });


    },

    openResourcesModal() {
      this.showResourcesTableModal = true;
    },

    /*
     * Set the initial, internal value for the field.
     */
    setInitialValue() {
      this.value = this.field.value || ''
    },

    /**
     * Fill the forms formData with details from this field
     */
     fill(formData) {
      console.log('asd',formData);
      
      this.fillIfVisible(
        formData,
        this.field.attribute,
        this.selectedResource ? this.selectedResource.value : ''
      )
      this.fillIfVisible(
        formData,
        `${this.field.attribute}_trashed`,
        this.withTrashed
      )
    },

    updateSelectionStatus(resource) {
      console.log('updateSelectionStatus');
      
      let selectedResource = {
        value: resource.id.value,
        display: resource.title
      };

      // uf the resource is already selected, remove it
      // if (this.value.map(v => v.value).indexOf(resource.id.value) > -1) {
      //   this.value = this.value.filter(v => v.value !== resource.id.value);
      // } else {
      // otherwise add it
      //   this.value.push(selectedResource);
      // }
    },
  },

  mounted() {
    this.fetchResources();
    this.initializeComponent();

    

  },


  computed: {

    // TODO: Handle filter types better
    filteredResources() {

      // return this.availableResources;
      // Helper function to check if a resource matches a filter
      const filterMatches = (resource, filter) => {
        if (filter.value === 'all') return true;

        const matchingField = resource.fields.find(field => {
          return field.attribute === filter.name;
        });

        let matchingFieldLabel = matchingField.value;

        if (typeof matchingFieldLabel === 'object') {


          if (typeof matchingFieldLabel[0] == 'string') {
            return matchingField && matchingFieldLabel.includes(filter.value);
          }

          matchingFieldLabel = matchingField.value[0]?.display;
        }




        return matchingField && matchingFieldLabel === filter.value;
      };

      // Helper function to check if a resource matches the search term
      const searchMatches = (resource) => {
        const searchLowerCase = this.search.toLowerCase();

        // TODO: Handle search types better
        const foundInSearchableFields = this.field?.searchableFields?.some(field => {
          const searchableField = resource.fields.find(_field => _field.attribute === field);
          return searchableField && searchableField.value.toLowerCase().includes(searchLowerCase);
        });

        return foundInSearchableFields || resource.title.toLowerCase().includes(searchLowerCase);
      };

      // Filter resources based on filters and search term
      return this.resources.filter(resource =>
        this.availableFilters.every(filter => filterMatches(resource, filter)) && searchMatches(resource)
      );
    },


  },
}
</script>
<style>
.max-h-96 {
  max-height: 24rem;
}
.sticky {
  position: sticky;
}
</style>