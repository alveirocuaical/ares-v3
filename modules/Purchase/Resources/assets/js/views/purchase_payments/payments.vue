<template>
    <el-dialog :title="title" :visible="showDialog" @close="close" @open="getData" width="65%">
        <div class="form-body">
            <div class="row">
                <div class="col-md-12" v-if="records.length > 0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha de pago</th>
                                <th>Método de pago</th>
                                <th>Origen de Pago</th>
                                <th>Referencia</th>
                                <th>Archivo</th>
                                <th class="text-right">Monto</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in records" :key="index">
                                <template v-if="row.id">
                                    <td>PAGO-{{ row.id }}</td>
                                    <td>{{ row.date_of_payment }}</td>
                                    <td>{{ row.payment_method_name || row.payment_method_type_description || '-' }}</td>
                                    <td>{{ row.destination_description }}</td>
                                    <td>
                                        <template v-if="row.is_retention">
                                            <div>{{ formatRetentionLabel(row) }}</div>
                                            <small class="form-text text-muted">Referencia: {{ row.reference }}</small>
                                        </template>
                                        <template v-else>
                                            {{ row.reference }}
                                        </template>
                                    </td>
                                    <td class="text-center">
                                        <button  type="button" v-if="row.filename" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickDownloadFile(row.filename)">
                                            <i class="fas fa-file-download"></i>
                                        </button>
                                    </td>
                                    <td class="text-right">{{ row.payment | numberFormat }}</td>
                                    <td class="series-table-actions text-right">
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>
                                        <!--<el-button type="danger" icon="el-icon-delete" plain @click.prevent="clickDelete(row.id)"></el-button>-->
                                    </td>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.date_of_payment}">
                                            <el-date-picker v-model="row.date_of_payment"
                                                            type="date"
                                                            :clearable="false"
                                                            format="dd/MM/yyyy"
                                                            value-format="yyyy-MM-dd"></el-date-picker>
                                            <small class="form-control-feedback" v-if="row.errors.date_of_payment" v-text="row.errors.date_of_payment[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.payment_method_type_id}">
                                            <el-select v-model="row.payment_method_id">
                                                <el-option v-for="option in payment_methods" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="row.errors.payment_method_type_id" v-text="row.errors.payment_method_type_id[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.payment_destination_id}">
                                            <el-checkbox v-model="row.is_retention" @change="onRetentionChange(index)">Retención</el-checkbox>
                                            <el-select v-model="row.payment_destination_id" filterable :disabled="row.payment_destination_disabled" v-if="!row.is_retention">
                                                <el-option v-for="option in payment_destinations" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="row.errors.payment_destination_id" v-text="row.errors.payment_destination_id[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.reference}">
                                            <template v-if="row.is_retention">
                                                <div class="row">
                                                    <div class="col-12 col-sm-6 mb-2">
                                                        <small class="form-text text-muted">Base:</small>
                                                        <el-input v-model="row.base_amount" @input="onRetentionTypeChange(index)" @blur="onRetentionTypeChange(index)"></el-input>
                                                    </div>
                                                    <div class="col-12 col-sm-6 mb-2">
                                                        <small class="form-text text-muted">Tipo retención</small>
                                                        <el-select v-model="row.retention_type_id" placeholder="Tipo retención" @change="onRetentionTypeChange(index)">
                                                            <el-option v-for="option in retention_types" :key="option.id" :value="option.id" :label="(option.name || option.description) + ' - ' + ( ((Number(option.rate || 0) / (Number(option.conversion || 100))) * 100).toFixed(2) + '%' )"></el-option>
                                                        </el-select>
                                                    </div>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <el-input v-model="row.reference"></el-input>
                                            </template>
                                            <small class="form-control-feedback" v-if="row.errors.reference" v-text="row.errors.reference[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0">
                                            
                                            <el-upload
                                                    :data="{'index': index}"
                                                    :headers="headers"
                                                    :multiple="false"
                                                    :on-remove="handleRemove"
                                                    :action="`/finances/payment-file/upload`"
                                                    :show-file-list="true"
                                                    :file-list="fileList"
                                                    :on-success="onSuccess"
                                                    :limit="1"
                                                    >
                                                <el-button slot="trigger" type="primary">Seleccione un archivo</el-button>
                                            </el-upload>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.payment}">
                                            <el-input v-model="row.payment"></el-input>
                                            <small class="form-control-feedback" v-if="row.errors.payment" v-text="row.errors.payment[0]"></small>
                                        </div>
                                    </td>
                                    <td class="series-table-actions text-right">
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickSubmit(index)">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickCancel(index)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </template>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="2" class="text-left footer-col">
                                    <div class="mb-1"><strong>Retenciones pagadas</strong></div>
                                    <div v-if="retentionPaymentsRecords.length > 0">
                                        <div v-for="(r, i) in retentionPaymentsRecords" :key="'rpaid-'+i" class="d-flex justify-content-between mb-1">
                                            <div><strong>{{ formatRetentionLabel(r) }}</strong></div>
                                            <div><strong>{{ (Number(r.payment) || 0) | numberFormat }}</strong></div>
                                        </div>
                                        <div class="d-flex justify-content-between"><div><strong>Total</strong></div><div><strong>{{ retentionPaid | numberFormat }}</strong></div></div>
                                    </div>
                                    <div v-else>-</div>
                                </td>

                                <td colspan="2" class="text-left footer-col">
                                    <div class="mb-1"><strong>Retenciones por pagar</strong></div>
                                    <div v-if="applied_retention_types && applied_retention_types.length > 0">
                                        <div v-for="(ret, i) in applied_retention_types" :key="'rapplied-'+i" class="d-flex justify-content-between mb-1">
                                            <div><strong>{{ formatAppliedRetentionLabel(ret) }}</strong></div>
                                            <div><strong>{{ (parseFloat(ret.amount) || 0) | numberFormat }}</strong></div>
                                        </div>
                                        <div class="d-flex justify-content-between"><div><strong>Total aplicado</strong></div><div><strong>{{ total_retention | numberFormat }}</strong></div></div>
                                        <div class="d-flex justify-content-between"><div><strong>Por pagar</strong></div><div><strong>{{ retentionPending | numberFormat }}</strong></div></div>
                                    </div>
                                    <div v-else>-</div>
                                </td>

                                <td colspan="2" class="text-right footer-col">
                                    <div class="mb-1"><strong>Pagos realizados</strong></div>
                                    <div v-if="paymentsRecords.length > 0">
                                        <div v-for="(p, i) in paymentsRecords" :key="'ppaid-'+i" class="d-flex justify-content-between mb-1">
                                            <div><strong>{{ p.payment_method_name || p.payment_method_type_description || 'Pago' }}</strong></div>
                                            <div><strong>{{ (Number(p.payment) || 0) | numberFormat }}</strong></div>
                                        </div>
                                        <div class="d-flex justify-content-between"><div><strong>Total</strong></div><div><strong>{{ paymentsPaid | numberFormat }}</strong></div></div>
                                    </div>
                                    <div v-else>-</div>
                                </td>

                                <td colspan="2" class="text-right">
                                    <div><strong>Pagos por pagar</strong></div>
                                    <div><strong>Total pendiente:</strong> {{ purchase.total_difference | numberFormat }}</div>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col-md-12 text-center pt-2" v-if="showAddButton && ( (purchase.total_difference > 0) || (retention_types.length > 0) || (applied_retention_types.length > 0) )">
                    <el-button type="primary" icon="el-icon-plus" @click="clickAddRow">Nuevo</el-button>
                </div>
            </div>
        </div>
    </el-dialog>

</template>

<script>

    import {deletable} from '@mixins/deletable'

    export default {
        props: ['showDialog', 'purchaseId'],
        mixins: [deletable],
        data() {
            return {
                title: null,
                resource: 'purchase-payments',
                records: [],
                retention_types: [],
                applied_retention_types: [],
                total_retention: 0,
                payment_destinations: [],
                payment_method_types: [],
                payment_methods: [],
                headers: headers_token,
                index_file: null,
                fileList: [],
                showAddButton: true,
                purchase: {}
            }
        },
        async created() {
            await this.initForm();
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.payment_destinations = response.data.payment_destinations
                    this.payment_method_types = response.data.payment_method_types;
                    this.payment_methods = response.data.payment_methods;
                    this.retention_types = response.data.retention_types || [];
                })
        },
        computed: {
            retentionPaymentsRecords() {
                return (this.records || []).filter(r => r.is_retention);
            },
            retentionPaid() {
                return this.retentionPaymentsRecords.reduce((sum, r) => sum + (Number(r.payment) || 0), 0);
            },
            paymentsRecords() {
                return (this.records || []).filter(r => !r.is_retention);
            },
            paymentsPaid() {
                return this.paymentsRecords.reduce((sum, r) => sum + (Number(r.payment) || 0), 0);
            },
            retentionPending() {
                return Math.max((Number(this.total_retention) || 0) - (Number(this.retentionPaid) || 0), 0);
            }
        },
        methods: {
            clickDownloadFile(filename) {
                window.open(
                    `/finances/payment-file/download-file/${filename}/purchases`,
                    "_blank"
                );
            },
            onSuccess(response, file, fileList) {

                // console.log(response, file, fileList)
                this.fileList = fileList

                if (response.success) {

                    this.index_file = response.data.index
                    this.records[this.index_file].filename = response.data.filename
                    this.records[this.index_file].temp_path = response.data.temp_path

                } else {
                    this.$message.error(response.message)
                }

                // console.log(this.records)
            
            },
            handleRemove(file, fileList) {       
                
                this.records[this.index_file].filename = null
                this.records[this.index_file].temp_path = null
                this.fileList = []
                this.index_file = null

            }, 
            initForm() {
                this.records = [];
                this.fileList = [];
                this.showAddButton = true;
            },
            async getData() {
                this.initForm();
                await this.$http.get(`/${this.resource}/purchase/${this.purchaseId}`)
                    .then(response => {
                        this.purchase = response.data;
                        this.title = 'Pagos de la compra: '+this.purchase.number_full;
                    });
                const subtotal = Number(this.purchase.subtotal || 0);
                this.applied_retention_types = (this.purchase.taxes || []).filter(t => t.is_retention && ((parseFloat(t.retention) || 0) > 0 || t.apply === true)).map(t => {
                    const rate = Number(t.rate || 0);
                    const conversion = Number(t.conversion || 100);
                    let amount = 0;
                    if ((parseFloat(t.retention) || 0) > 0) {
                        amount = Number(t.retention);
                    } else if (t.is_fixed_value) {
                        amount = rate;
                    } else {
                        amount = subtotal * (rate / conversion);
                    }
                    return Object.assign({}, t, { amount: Number(amount.toFixed(2)), rate: rate, conversion: conversion });
                });
                this.total_retention = this.applied_retention_types.reduce((sum, t) => sum + (Number(t.amount) || 0), 0);
                await this.$http.get(`/${this.resource}/records/${this.purchaseId}`)
                    .then(response => {
                        this.records = response.data.data
                    });
                this.$eventHub.$emit('reloadDataToPay')

            },
            clickAddRow() {
                this.records.push({
                    id: null,
                    date_of_payment: moment().format('YYYY-MM-DD'),
                    payment_method_id: null,
                    payment_method_type_id: null,
                    payment_destination_id:null,
                    reference: null,
                    is_retention: false,
                    base_amount: 0,
                    payment_destination_disabled: false,
                    retention_type_id: null,
                    filename: null,
                    temp_path: null,
                    payment: 0,
                    errors: {},
                    loading: false
                });
                const idx = this.records.length - 1;
                if ((parseFloat(this.purchase.total_difference) || 0) <= 0 && (this.retention_types.length > 0 || this.applied_retention_types.length > 0)) {
                    this.records[idx].is_retention = true;
                    this.onRetentionChange(idx);
                }
                this.showAddButton = false;
            },
            clickCancel(index) {
                this.records.splice(index, 1);
                this.showAddButton = true;
                this.fileList = []
            },
            clickSubmit(index) {

                if(!this.records[index].is_retention && this.records[index].payment > parseFloat(this.purchase.total_difference)) {
                    this.$message.error('El monto ingresado supera al monto pendiente de pago, verifique.');
                    return;
                } 

                let form = {
                    id: this.records[index].id,
                    purchase_id: this.purchaseId,
                    date_of_payment: this.records[index].date_of_payment,
                    payment_method_id: this.records[index].payment_method_id,
                    payment_method_type_id: this.records[index].payment_method_type_id,
                    payment_destination_id: this.records[index].payment_destination_id,
                    is_retention: this.records[index].is_retention || false,
                    retention_type_id: this.records[index].retention_type_id || null,
                    base_amount: this.records[index].base_amount || null,
                    reference: this.records[index].reference,
                    filename: this.records[index].filename,
                    temp_path: this.records[index].temp_path,
                    payment: this.records[index].payment,
                }

                this.$http.post(`/${this.resource}`, form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            this.getData();
                            // this.initpurchaseTypes()
                            this.$eventHub.$emit('reloadData')
                            this.showAddButton = true;
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.records[index].errors = error.response.data;
                        } else {
                            console.log(error);
                        }
                    })
            }, 
            close() {
                this.$emit('update:showDialog', false);
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>{
                        this.getData()
                        this.$eventHub.$emit('reloadData')
                    }
                )
            }
            ,
            getCashDestinationId() {
                if (!this.payment_destinations || this.payment_destinations.length === 0) return null;
                const found = this.payment_destinations.find(d => (d.description || '').toString().toLowerCase().includes('caja') || (d.name || '').toString().toLowerCase().includes('caja'));
                return (found) ? found.id : this.payment_destinations[0].id;
            },
            onRetentionChange(index) {
                const row = this.records[index];
                if (row.is_retention) {
                    row.payment_destination_disabled = true;
                    row.payment_destination_id = this.getCashDestinationId();
                    row.base_amount = Number(this.purchase.subtotal || 0);
                    row.reference = Number(row.base_amount || 0).toFixed(2);
                    if (row.retention_type_id) this.onRetentionTypeChange(index);
                } else {
                    row.payment_destination_disabled = false;
                    row.retention_type_id = null;
                    row.reference = null;
                    row.payment = 0;
                }
            },
            onRetentionTypeChange(index) {
                const row = this.records[index];
                if (!row.retention_type_id) return;
                const rt = this.retention_types.find(r => r.id == row.retention_type_id);
                if (!rt) return;
                const base = Number(row.base_amount || this.purchase.subtotal || 0);
                const rate = Number(rt.rate || 0);
                const conversion = Number(rt.conversion || 100);
                if (rt.is_fixed_value) {
                    row.payment = Number(rate.toFixed(2));
                } else {
                    const calc = base * (rate / conversion);
                    row.payment = Number(calc.toFixed(2));
                }
                row.reference = Number(base || 0).toFixed(2);
            },
            formatRetentionLabel(row) {
                if (!row) return '-';
                const rt = this.retention_types.find(r => r.id == row.retention_type_id);
                const name = (rt && (rt.name || rt.description)) || row.retention_type_description || row.retention_type_id || '-';
                if (!rt) return name;
                if (rt.is_fixed_value) return name;
                const rate = Number(rt.rate || 0);
                const conversion = Number(rt.conversion || 100);
                const percent = ((rate / conversion) * 100).toFixed(2) + '%';
                return name + ' - ' + percent;
            }
            ,
            formatAppliedRetentionLabel(ret) {
                if (!ret) return '-';
                const name = ret.name || ret.description || ret.id || '-';
                if (ret.is_fixed_value) return name;
                const rate = Number(ret.rate || 0);
                const conversion = Number(ret.conversion || 100);
                const percent = ((rate / conversion) * 100).toFixed(2) + '%';
                return name + ' - ' + percent;
            }
        }
    }
</script>

<style scoped>
/* Footer column divider (desktop) */
.footer-col {
    border-right: 1px solid #e9ecef;
    padding-right: 12px;
}
@media (max-width: 999px) {
    .footer-col { border-right: none; padding-right: 0; }
}
</style>
