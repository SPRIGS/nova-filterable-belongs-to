<template>
  <tr class="group">
    <!-- Resource Selection Checkbox -->
    <td v-if="shouldShowCheckboxes" :class="{
      'py-2': !shouldShowTight,
    }" class="td-fit pl-5 pr-5 dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 cursor-pointer">




      <input type="radio" :name="testId" class="hidden" :value="false" v-model="isChecked" />
      <input type="radio" :name="testId"
        class="w-4 h-4  bg-gray-100 border-gray-300 outline-none focus:outline-none focus:ring-0 cursor-pointer"
        :value="true" v-model="isChecked" @input="toggleSelection" v-if="shouldShowCheckboxes"
        :aria-label="__('Select Resource :title', { title: resource.title })" :data-testid="`${testId}-checkbox`"
        :dusk="`${resource['id'].value}-checkbox`">
    </td>

    <!-- Fields -->
    <td v-for="(field, index) in resource.fields" :key="field.uniqueKey" :class="{
      'px-6': index == 0 && !shouldShowCheckboxes,
      'px-2': index != 0 || shouldShowCheckboxes,
      'py-2': !shouldShowTight,
      'whitespace-nowrap': !field.wrapping,
    }" class="cursor-pointer dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
      <component :is="'index-' + field.component" :class="`text-${field.textAlign}`" :field="field" :resource="resource"
        :resource-name="resourceName" :via-resource="viaResource" :via-resource-id="viaResourceId" />
    </td>

    <td class="px-2 td-fit text-right align-middle dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
    </td>
  </tr>
</template>

<script>
import { Inertia } from '@inertiajs/inertia'

export default {
  emits: ['actionExecuted'],

  inject: [
    'authorizedToViewAnyResources',
    'authorizedToUpdateAnyResources',
    'authorizedToDeleteAnyResources',
    'authorizedToRestoreAnyResources',
  ],

  props: [
    'radioButtonName',
    'testId',
    'deleteResource',
    'restoreResource',
    'resource',
    'resourcesSelected',
    'resourceName',
    'relationshipType',
    'viaRelationship',
    'viaResource',
    'viaResourceId',
    'viaManyToMany',
    'checked',
    'actionsAreAvailable',
    'actionsEndpoint',
    'shouldShowCheckboxes',
    'shouldShowColumnBorders',
    'tableStyle',
    'updateSelectionStatus',
    'queryString',
    'clickAction',
  ],


  methods: {
    toggleSelection() {
      this.updateSelectionStatus(this.resource)
    },

  },

  computed: {


    isChecked() {
      return this.checked
    },




    shouldShowTight() {
      return this.tableStyle === 'tight'
    },


  },
}
</script>
