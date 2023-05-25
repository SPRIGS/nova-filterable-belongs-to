<template>
  <DefaultField 
    :field="field"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent">
    
    <template #field>
      <div class="block mb-2 " v-if="selectedResource?.display && selectedResource.display.length">{{ selectedResource.display }}</div>
      <div class="block help-text help-text mb-2" v-else>Please select an option</div>

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
                <SearchSearchInput debounce="100" @input="performResourceSearch" class="w-full">
                </SearchSearchInput>


                <div class="relative w-full " v-for="filter in availableFilters">
                  <span>{{ filter.label }}</span>
                  <SelectControl v-model:selected="filter.value" :options="filter.options"
                    @change="filterChanged(filter, $event)" size="sm" />
                </div>
              </div>

              <!-- End of Table filters -->

              <div class=" overflow-x-auto relative max-h-96 block">
                <table v-if="availableResources.length > 0" class="w-full divide-y divide-gray-100 dark:divide-gray-700"
                  data-testid="resource-table">
                  <ResourceTableHeader :resource-name="resourceName" 
                    :fields="[
                      { 
                        indexName: 'Title',
                        textAlign: 'left',
                      }
                    ]" 
                    class="sticky top-0" :should-show-column-borders="shouldShowColumnBorders" :should-show-checkboxes="true"
                    :sortable="sortable" @order="requestOrderByChange" @reset-order-by="resetOrderBy" />
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto" style="min-height: 24rem;">
                    <ModalResourceTableRow v-for="(resource, index) in filteredResources"
                      :testId="`${resourceName}-items-${index}`"
                      :radio-button-name="field.belongsToRelationship"
                      :checked="resource.value == selectedResourceId"
                      @click="selectResource(resource)"

                      :key="`${resource.id}-items-${index}`" 
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
import find from 'lodash/find'
import isNil from 'lodash/isNil'
import storage from '@/storage/BelongsToFieldStorage'
import {
  DependentFormField,
  HandlesValidationErrors,
  InteractsWithQueryString,
  PerformsSearches,
  TogglesTrashed,
} from '@/mixins'
import filled from '@/util/filled'
import ModalResourceTableRow from './ModalResourceTableRow.vue';

export default {
  name: 'FilterableBelongsToFormField',
  components: {ModalResourceTableRow},

  mixins: [
    DependentFormField,
    HandlesValidationErrors,
    InteractsWithQueryString,
    PerformsSearches,
    TogglesTrashed,
  ],

  props: {
    resourceId: {},
  },

  data: () => ({
    // Custom data
    showResourcesTableModal: false,
    availableFilters: [],


    availableResources: [],
    initializingWithExistingResource: false,
    createdViaRelationModal: false,
    selectedResource: null,
    selectedResourceId: null,
    softDeletes: false,
    withTrashed: false,
    search: '',
    relationModalOpen: false,
  }),

  /**
   * Mount the component.
   */
  mounted() {
    this.initializeComponent()
  },

  methods: {
    // custom methods
    openResourcesModal() {
      this.showResourcesTableModal = true;
    },

    initializeComponent() {
      this.withTrashed = false

      this.selectedResourceId = this.currentField.value

      if (this.editingExistingResource) {
        // If a user is editing an existing resource with this relation
        // we'll have a belongsToId on the field, and we should prefill
        // that resource in this field
        this.initializingWithExistingResource = true
        this.selectedResourceId = this.currentField.belongsToId
      } else if (this.viaRelatedResource) {
        // If the user is creating this resource via a related resource's index
        // page we'll have a viaResource and viaResourceId in the params and
        // should prefill the resource in this field with that information
        this.initializingWithExistingResource = true
        this.selectedResourceId = this.viaResourceId
      }

      if (this.shouldSelectInitialResource) {
        if (this.useSearchInput) {
          // If we should select the initial resource and the field is
          // searchable, we won't load all the resources but we will select
          // the initial option.
          this.getAvailableResources().then(() => this.selectInitialResource())
        } else {
          // If we should select the initial resource but the field is not
          // searchable we should load all of the available resources into the
          // field first and select the initial option.
          this.initializingWithExistingResource = false

          this.getAvailableResources().then(() => this.selectInitialResource())
        }
      } else if (!this.isSearchable) {
        // If we don't need to select an initial resource because the user
        // came to create a resource directly and there's no parent resource,
        // and the field is searchable we'll just load all of the resources.
        this.getAvailableResources()
      }

      this.determineIfSoftDeletes()

      this.field.fill = this.fill
    },

    /**
     * Select a resource using the <select> control
     */
    selectResourceFromSelectControl(value) {
      this.selectedResourceId = value
      this.selectInitialResource()

      if (this.field) {
        this.emitFieldValueChange(this.field.attribute, this.selectedResourceId)
      }
    },

    /**
     * Fill the forms formData with details from this field
     */
    fill(formData) {
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

    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources() {
      Nova.$progress.start()

      return storage
        .fetchAvailableResources(
          this.resourceName,
          this.field.attribute,
          this.queryParams
        )
        .then(({ data: { resources, softDeletes, withTrashed } }) => {
          Nova.$progress.done()

          if (this.initializingWithExistingResource || !this.isSearchable) {
            this.withTrashed = withTrashed
          }

          if (this.viaRelatedResource) {
            let selectedResource = find(resources, r =>
              this.isSelectedResourceId(r.value)
            )

            if (
              isNil(selectedResource) &&
              !this.shouldIgnoresViaRelatedResource
            ) {
              return Nova.visit('/404')
            }
          }

          // Turn off initializing the existing resource after the first time
          if (this.useSearchInput) {
            this.initializingWithExistingResource = false
          }
          this.availableResources = resources
          this.softDeletes = softDeletes
        })
        .catch(e => {
          Nova.$progress.done()
        })
    },

    /**
     * Determine if the relatd resource is soft deleting.
     */
    determineIfSoftDeletes() {
      return storage
        .determineIfSoftDeletes(this.field.resourceName)
        .then(response => {
          this.softDeletes = response.data.softDeletes
        })
    },

    /**
     * Determine if the given value is numeric.
     */
    isNumeric(value) {
      return !isNaN(parseFloat(value)) && isFinite(value)
    },

    /**
     * Select the initial selected resource
     */
    selectInitialResource() {
      this.selectedResource = find(this.availableResources, r =>
        this.isSelectedResourceId(r.value)
      )
    },

    /**
     * Toggle the trashed state of the search
     */
    toggleWithTrashed() {
      // Reload the data if the component doesn't have selected resource
      if (!filled(this.selectedResource)) {
        this.withTrashed = !this.withTrashed

        if (!this.useSearchInput) {
          this.getAvailableResources()
        }
      }
    },

    openRelationModal() {
      Nova.$emit('create-relation-modal-opened')
      this.relationModalOpen = true
    },

    closeRelationModal() {
      this.relationModalOpen = false
      Nova.$emit('create-relation-modal-closed')
    },

    handleSetResource({ id }) {
      this.closeRelationModal()
      this.selectedResourceId = id
      this.initializingWithExistingResource = true
      this.createdViaRelationModal = true
      this.getAvailableResources().then(() => {
        this.selectInitialResource()

        this.emitFieldValueChange(this.field.attribute, this.selectedResourceId)
      })
    },

    performResourceSearch(search) {
      console.log('performResourceSearch', search);
      if (this.useSearchInput) {
        this.performSearch(search)
      } else {
        this.search = search
      }
    },

    clearResourceSelection() {
      this.clearSelection()

      if (this.viaRelatedResource && !this.createdViaRelationModal) {
        this.updateQueryString({
          viaResource: null,
          viaResourceId: null,
          viaRelationship: null,
          relationshipType: null,
        }).then(() => {
          Nova.$router.reload({
            onSuccess: () => {
              this.initializingWithExistingResource = false
              this.initializeComponent()
            },
          })
        })
      } else {
        if (this.createdViaRelationModal) {
          this.createdViaRelationModal = false
          this.initializingWithExistingResource = false
        }

        this.getAvailableResources()
      }
    },

    onSyncedField() {
      if (this.viaRelatedResource) {
        return
      }

      let emitChangesEvent = !this.isSelectedResourceId(this.currentField.value)

      this.initializeComponent()

      if (!this.editingExistingResource && emitChangesEvent) {
        this.emitFieldValueChange(this.field.attribute, this.selectedResourceId)
      }
    },

    isSelectedResourceId(value) {
      return (
        !isNil(value) &&
        value?.toString() === this.selectedResourceId?.toString()
      )
    },
  },

  computed: {

    // Custom computed functionalities
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


    /**
     * Determine if we are editing and existing resource
     */
    editingExistingResource() {
      return filled(this.field.belongsToId)
    },

    /**
     * Determine if we are creating a new resource via a parent relation
     */
    viaRelatedResource() {
      return Boolean(
        this.viaResource === this.field.resourceName &&
          this.field.reverse &&
          this.viaResourceId
      )
    },

    /**
     * Determine if we should select an initial resource when mounting this field
     */
    shouldSelectInitialResource() {
      return Boolean(
        this.editingExistingResource ||
          this.viaRelatedResource ||
          this.currentField.value
      )
    },

    /**
     * Determine if the related resources is searchable
     */
    isSearchable() {
      return Boolean(this.currentField.searchable)
    },

    /**
     * Get the query params for getting available resources
     */
    queryParams() {
      return {
        current: this.selectedResourceId,
        first: this.shouldLoadFirstResource,
        search: this.search,
        withTrashed: this.withTrashed,
        resourceId: this.resourceId,
        viaResource: this.viaResource,
        viaResourceId: this.viaResourceId,
        viaRelationship: this.viaRelationship,
        component: this.field.dependentComponentKey,
        dependsOn: this.encodedDependentFieldValues,
        editing: true,
        editMode:
          isNil(this.resourceId) || this.resourceId === ''
            ? 'create'
            : 'update',
      }
    },

    shouldLoadFirstResource() {
      return (
        (this.initializingWithExistingResource &&
          !this.shouldIgnoresViaRelatedResource) ||
        Boolean(this.currentlyIsReadonly && this.selectedResourceId)
      )
    },

    shouldShowTrashed() {
      return (
        this.softDeletes &&
        !this.viaRelatedResource &&
        !this.currentlyIsReadonly &&
        this.currentField.displaysWithTrashed
      )
    },

    authorizedToCreate() {
      return find(Nova.config('resources'), resource => {
        return resource.uriKey === this.field.resourceName
      }).authorizedToCreate
    },

    canShowNewRelationModal() {
      return (
        this.currentField.showCreateRelationButton &&
        !this.shownViaNewRelationModal &&
        !this.viaRelatedResource &&
        !this.currentlyIsReadonly &&
        this.authorizedToCreate
      )
    },

    /**
     * Return the placeholder text for the field.
     */
    placeholder() {
      return this.currentField.placeholder || this.__('—')
    },

    /**
     * Return the field options filtered by the search string.
     */
    filteredResources() {
      // if (!this.isSearchable) {
        return this.availableResources.filter(option => {
          return (
            option.display.toLowerCase().indexOf(this.search.toLowerCase()) >
              -1 || new String(option.value).indexOf(this.search) > -1
          )
        })
      // }
      // return this.availableResources
    },

    shouldIgnoresViaRelatedResource() {
      return this.viaRelatedResource && filled(this.search)
    },

    useSearchInput() {
      return this.isSearchable || this.viaRelatedResource
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