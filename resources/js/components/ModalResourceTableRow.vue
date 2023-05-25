<template>
  <tr class="group cursor-pointer" @click.stop="toggleSelection">
    <!-- Resource Selection Checkbox -->
    <td v-if="shouldShowCheckboxes" :class="{
      'py-2': !shouldShowTight,
    }" class="td-fit pl-5 pr-5 dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900 cursor-pointer">


      <input type="radio" :name="resourceName" ref="checkbox"
        class="w-4 h-4  bg-gray-100 border-gray-300 outline-none focus:outline-none focus:ring-0 cursor-pointer"
        :checked="isChecked"
        :value="resource.value" v-model="isChecked" v-if="shouldShowCheckboxes"
        :aria-label="__('Select Resource :title', { title: resource.display })" :data-testid="`${testId}-checkbox`"
        :dusk="`${resource['id']}-checkbox`">
    </td>

    <td class="px-2 align-middle dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
      {{ resource.display }}
    </td>

    <td class="px-2 td-fit text-right align-middle dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-900">
    </td>
  </tr>
</template>

<script>
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
      let checkbox = this.$refs.checkbox
      if (checkbox.checked) {
        return
      }
      checkbox.checked = !checkbox.checked
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
