<template>
  <section>
    <el-icon :class="showClass" style="display: contents">
      <component :is="ElSvg[elSvgName]" />
    </el-icon>
    <span v-if="title">{{ title }}</span>
  </section>
</template>

<script setup>
import * as ElSvg from '@element-plus/icons-vue'
const props = defineProps({
  elSvgName: {
    require: true,
    default: 'Fold',
    type: String
  },
  showClass: {
    required: false,
    default: '',
    type: String
  },
  title: {
    required: false,
    default: ''
  }
})
const { elSvgName, showClass } = props
</script>

<style scoped lang="scss">
.el-svg-icon {
  width: 1em;
  height: 1em;
  margin-left: -2px; //el-svg-icon has some margin
  font-size: 20px !important;
  text-align: left !important;
}
</style>
