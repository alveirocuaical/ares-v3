<template>
    <div>
        <!-- HEADER -->
        <div class="page-header pr-0">
            <h2>
                <a href="/restaurant">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         style="margin-top:-5px">
                        <path d="M4 4h6v6H4z"/>
                        <path d="M14 4h6v6h-6z"/>
                        <path d="M14 14h6v6h-6z"/>
                        <path d="M4 14h6v6H4z"/>
                    </svg>
                </a>
            </h2>

            <ol class="breadcrumbs">
                <li class="active"><span>Productos Restaurante</span></li>
            </ol>

            <div class="right-wrapper pull-right">
                <button type="button"
                        class="btn btn-primary btn-sm mt-2 mr-2"
                        @click.prevent="clickCreate()">
                    <i class="fa fa-plus-circle"></i> Nuevo
                </button>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card mb-0">
            <div class="card-body">
                <data-table :resource="resource" ref="dataTable">

                    <tr slot="heading">
                        <th>#</th>
                        <th>Unidad</th>
                        <th>Cód. Interno</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th class="text-left">Stock</th>
                        <th class="text-right">P. Venta</th>
                        <th class="text-center">Visible</th>
                        <th class="text-right">Acciones</th>
                    </tr>

                    <template slot-scope="{ row }">

                        <tr :class="{ disable_color : !row.active }">
                            <td>{{ row.id }}</td>
                            <td>{{ row.unit_type ? row.unit_type.name : '-' }}</td>
                            <td>{{ row.internal_id }}</td>
                            <td>{{ row.name }}</td>
                            <td>{{ row.category ? row.category.name : '-' }}</td>
                            <td>{{ formatStock(row.stock) }}</td>
                            <td class="text-right">{{ row.sale_unit_price | numberFormat }}</td>

                            <!-- Check visible -->
                            <td class="text-center">
                                <el-tooltip
                                    effect="dark"
                                    content="Si está activado, este producto se mostrará en el menú del restaurante."
                                    placement="top"
                                    :open-delay="200"
                                >
                                    <el-checkbox
                                        :value="!!row.apply_restaurant"
                                        @change="val => toggleVisible(row, val)">
                                    </el-checkbox>
                                </el-tooltip>
                            </td>

                            <td class="text-right">
                                <div class="d-inline">
                                    <el-button
                                        type="button"
                                        class="btn waves-effect waves-light btn-xs btn-info"
                                        @click.prevent="clickCreate(row.id)">
                                        Editar
                                    </el-button>
                                </div>
                                <div class="d-inline">
                                    <el-button
                                        type="button"
                                        class="btn waves-effect waves-light btn-xs btn-danger"
                                        @click.prevent="clickDelete(row.id)">
                                        Eliminar
                                    </el-button>
                                </div>
                            </td>
                        </tr>

                    </template>

                </data-table>
            </div>
        </div>

        <!-- FORMULARIO -->
        <items-form :showDialog.sync="showDialog" :recordId="recordId"></items-form>
    </div>
</template>

<script>
import ItemsForm from '../items_ecommerce/form.vue'
import DataTable from '../../../components/DataTable.vue'
import {deletable} from '../../../mixins/deletable'

export default {
    mixins: [deletable],
    components: { ItemsForm, DataTable },

    data() {
        return {
            resource: 'restaurant',
            recordId: null,
            showDialog: false
        }
    },

    methods: {
        clickCreate(id = null) {
            this.recordId = id
            this.showDialog = true
        },

        formatStock(value) {
            return value ? parseFloat(value).toFixed(2) : '0.00'
        },

        toggleVisible(row, val) {
            row.apply_restaurant = val
            this.$http.post(`/${this.resource}/items/toggle-visible`, {
                id: row.id,
                visible: row.apply_restaurant
            }).then(() => {
                this.$message.success("Actualizado")
            })
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/delete/${id}`).then(() =>
                this.$eventHub.$emit('reloadData')
            )
        },
    }
}
</script>

<style scoped>
.disable_color {
    opacity: 0.5;
}
</style>
