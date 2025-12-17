<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close" top="7vh" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="row"> 
                    <div class="col-md-12">
                        <div class="form-group" :class="{'has-danger': errors.tax_id}">
                            <label class="control-label">Retención</label>
                            <el-select v-model="form.tax_id"  filterable @change="calculatePreview">
                                <el-option v-for="option in retentiontaxes" :key="option.id" :value="option.id" :label="`${option.name} - ${option.rate}%`"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                        </div>
                    </div> 
                    <div class="col-md-12 mt-2">
                        <div class="form-group">
                            <label class="control-label">Base</label>
                            <el-select v-model="base_type" @change="calculatePreview">
                                <el-option label="Subtotal" value="subtotal"></el-option>
                                <el-option label="Base personalizada" value="custom"></el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-md-12" v-if="base_type === 'custom'">
                        <div class="form-group">
                            <label class="control-label">Base personalizada</label>
                            <el-input v-model="custom_base" @input="calculatePreview"></el-input>
                        </div>
                    </div>
                    <div class="col-md-12 mt-2" v-if="form.tax_id">
                        <div class="alert alert-info">
                            <div>Base seleccionada: {{ preview_base_amount || 0 }}</div>
                            <div>Porcentaje: {{ preview_rate || 0 }}%</div>
                            <div>Valor Retención: {{ preview_retention }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right pt-2 mt-3">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button class="add" type="primary" native-type="submit" v-if="form.tax_id">{{titleAction}}</el-button>
            </div>
        </form>
 

    </el-dialog>
</template>
<style>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}
</style>

<script>

    export default {
        props: ['recordItem','showDialog'],
        data() {
            return {
                titleAction: '',
                titleDialog: '',
                resource: 'co-documents',
                errors: {},
                form: {},
                taxes:[],
                // base selection for retention
                base_type: 'subtotal',
                custom_base: 0,
            }
        },
        computed: { 
            retentiontaxes() {
                return this.taxes.filter(tax => tax.is_retention);
            },
            // retentionSelected() {
            // if (this.retention.retention_id == null) return { rate: 0 };

            //     return this.taxes.find(row => row.id == this.retention.retention_id);
            // }, 
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/table/taxes`).then(response => {
                this.taxes = response.data;
            })

        },
        methods: {
            initForm() {

                this.errors = {};

                this.form = {
                    tax_id: null,
                    base_type: 'subtotal',
                    base_amount: null,
                };
                this.preview_rate = 0;
                this.preview_base_amount = 0;
                this.preview_retention = 0;
 
            },
            async create() {

                this.titleDialog = 'Agregar retención';
                this.titleAction = 'Agregar';

            },
            close() {
                this.initForm()
                this.$emit('update:showDialog', false)
            },
            async changeItem() {


            }, 
            async clickAddItem() {

                let baseAmount = null;
                if (this.base_type === 'custom') {
                    baseAmount = Number(this.custom_base || 0);
                }
                const payload = Object.assign({}, this.form, { base_type: this.base_type, base_amount: baseAmount });
                this.$emit('add', payload);
                this.initForm();
                
            },
            calculatePreview() {
                const tax = this.taxes.find(t => t.id === this.form.tax_id);
                if (!tax) {
                    this.preview_rate = 0;
                    this.preview_base_amount = 0;
                    this.preview_retention = 0;
                    return;
                }

                this.preview_rate = Number(tax.rate || 0);
                let base = 0;
                if (this.base_type === 'custom') {
                    base = Number(this.custom_base || 0);
                } else {
                    const parentSubtotal = (this.$parent && this.$parent.support_document && this.$parent.support_document.subtotal) ? Number(this.$parent.support_document.subtotal) : 0;
                    base = parentSubtotal;
                }

                this.preview_base_amount = Number(base || 0);
                const calc = Number(base || 0) * (Number(tax.rate || 0) / Number(tax.conversion || 100));
                this.preview_retention = Number(calc.toFixed(2));
            }
        }
    }

</script>
