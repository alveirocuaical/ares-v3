<template>
    <div>
        <el-dialog :visible="showDialog" title="Crear Conciliación Bancaria" width="75%" @close="close">
            <el-form :model="form" label-width="150px">
                <div class="row">
                    <div class="col-md-4">
                        <el-form-item label="Fecha de Creación">
                            <el-date-picker v-model="form.date" type="date" value-format="yyyy-MM-dd" style="width:100%"></el-date-picker>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item label="Mes de Conciliación">
                            <el-date-picker
                                v-model="form.month"
                                type="month"
                                value-format="yyyy-MM"
                                style="width:100%"
                                @change="() => onMonthOrAccountChange(1, true)"
                            ></el-date-picker>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item label="Cuenta Bancaria">
                            <el-select
                                v-model="form.bank_account_id"
                                placeholder="Seleccione"
                                style="width:100%"
                                @change="() => onMonthOrAccountChange(1, true)"
                            >
                                <el-option
                                    v-for="acc in bankAccounts"
                                    :key="acc.id"
                                    :label="acc.description + ' - ' + acc.number"
                                    :value="acc.id"
                                ></el-option>
                            </el-select>
                        </el-form-item>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <el-form-item label="Saldo Libro">
                            <el-input :value="saldo_libro" readonly></el-input>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item label="Saldo Extracto">
                            <el-input v-model="form.saldo_extracto" @input="updateDiferencia"></el-input>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item label="Diferencia">
                            <el-input v-model="form.diferencia" ></el-input>
                        </el-form-item>
                    </div>
                </div>
            </el-form>

            <div class="row mb-2">
                <div class="col-md-4">
                    <el-alert
                        :title="'Saldo Inicial: ' + saldo_inicial"
                        type="info"
                        show-icon
                        :closable="false"
                    ></el-alert>
                </div>
            </div>
            
            <el-table
                :data="movimientos"
                ref="movimientosTable"
                style="width: 100%; margin-bottom: 15px;"
                @select="handleRowSelect"
                @select-all="handleSelectAll"
                :row-key="getRowKey"
                border
                show-summary
                :summary-method="getSummary"
            >
                <el-table-column type="selection" width="55"></el-table-column>
                <el-table-column prop="documento" label="Documento" width="120"></el-table-column>
                <el-table-column prop="concepto" label="Concepto"></el-table-column>
                <el-table-column prop="documento_tercero" label="CC/NIT" width="120"></el-table-column>
                <el-table-column prop="nombre_tercero" label="Nombre Tercero"></el-table-column>
                <!-- <el-table-column label="N. de Cheque" width="120">
                    <template slot-scope="scope">
                        <el-input v-model="scope.row.numero_cheque" placeholder="N° Cheque"></el-input>
                    </template>
                </el-table-column> -->
                <el-table-column prop="debit" label="Débito" width="100"></el-table-column>
                <el-table-column prop="credit" label="Crédito" width="100"></el-table-column>
            </el-table>
            <el-pagination
                background
                layout="total, prev, pager, next"
                :total="movimientosPagination.total"
                :page-size="movimientosPagination.per_page"
                :current-page="movimientosPagination.current_page"
                @current-change="handleMovimientosPageChange"
                style="margin-bottom: 15px;"
            ></el-pagination>
            <div class="mb-2">
                <strong>Seleccionados en extracto:</strong>
                {{ selectedMovimientosIds.length }}
                <!-- <br>
                <small>
                    IDs seleccionados: [
                    {{ selectedMovimientosIds.join(', ') }}
                    ]
                </small>
                <br>
                <small>
                    IDs página actual: [
                    {{ movimientos.map(getRowKey).join(', ') }}
                    ]
                </small> -->
            </div>

            <div class="row mb-2">
                <div class="col-md-12">
                    <el-alert
                        :title="diferenciaSeleccionados"
                        type="success"
                        show-icon
                        :closable="false"
                    ></el-alert>
                </div>
            </div>
            
            <span slot="footer" class="dialog-footer">
                <el-button @click="close">Cancelar</el-button>
                <el-button type="primary" @click="submitForm">Ajustar Pendientes</el-button>
            </span>
        </el-dialog>
        <form-reconciliation
            :showDialog.sync="showDialogReconciliation"
            :saldo_libro="datosConciliacion ? datosConciliacion.saldo_libro : ''"
            :saldo_extracto="datosConciliacion ? datosConciliacion.saldo_extracto : ''"
            :movimientos="datosConciliacion ? datosConciliacion.movimientos : []"
            :movimientosSeleccionados="datosConciliacion ? datosConciliacion.movimientos_seleccionados : []"
        ></form-reconciliation>
    </div>
</template>

<script>
import formReconciliation from "./partials/formReconciliation.vue";
import moment from "moment";
export default {
    components: { formReconciliation },
    props: {
        showDialog: { type: Boolean, required: true },
        bankAccounts: { type: Array, default: () => [] }
    },
    data() {
        return {
            form: {
                date: moment().format('YYYY-MM-DD'),
                month: '',
                bank_account_id: null,
                saldo_extracto: '',
                diferencia: '',
            },
            saldo_inicial: '',
            saldo_final: '',
            saldo_libro: '',
            total_debit: 0,
            total_credit: 0,
            movimientos: [],
            movimientosPagination: {
                total: 0,
                per_page: 5,
                current_page: 1,
                last_page: 1,
            },
            showDialogReconciliation: false,
            datosConciliacion: null,
            loadingMovimientos: false,
            selectedMovimientosIds: [], // <-- Solo IDs seleccionados
            selectedMovimientos: [],
        }
    },
    computed: {
        diferenciaSeleccionados() {
            let totalDebito = 0;
            let totalCredito = 0;
            this.selectedMovimientos.forEach(mov => {
                totalDebito += parseFloat((mov.debit + '').replace(/\./g, '').replace(',', '.')) || 0;
                totalCredito += parseFloat((mov.credit + '').replace(/\./g, '').replace(',', '.')) || 0;
            });
            return `Total seleccionados - Débito: ${totalDebito.toLocaleString('es-CO', { minimumFractionDigits: 2 })} | Crédito: ${totalCredito.toLocaleString('es-CO', { minimumFractionDigits: 2 })} | Diferencia: ${(totalDebito - totalCredito).toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
        },
    },
    watch: {
        showDialog(val) {
            console.log('showDialog cambió:', val);
            if (!val) this.resetForm();
        }
    },
    methods: {
        handleSave(form) {
            // Guarda los datos del form principal
            this.datosConciliacion = form;
            this.showDialogReconciliation = true;
            this.getRecords();
        },
        getSummary(param) {
            const { columns } = param;
            const sums = [];
            columns.forEach((column, index) => {
                if (index === 4) {
                    sums[index] = 'Total general';
                } else if (column.property === 'debit') {
                    const val = isNaN(this.total_debit) ? '0.00' : Number(this.total_debit).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                    sums[index] = val;
                } else if (column.property === 'credit') {
                    const val = isNaN(this.total_credit) ? '0.00' : Number(this.total_credit).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                    sums[index] = val;
                } else {
                    sums[index] = '';
                }
            });
            return sums;
        },
        handleRowSelect(selection, row) {
            const id = Number(row.id);
            // Si se seleccionó
            if (selection.some(r => Number(r.id) === id)) {
                if (!this.selectedMovimientos.some(m => Number(m.id) === id)) {
                    this.selectedMovimientos.push({ ...row });
                }
            } else {
                this.selectedMovimientos = this.selectedMovimientos.filter(m => Number(m.id) !== id);
            }
            // Mantén los IDs sincronizados si los usas en otros métodos
            this.selectedMovimientosIds = this.selectedMovimientos.map(m => Number(m.id));
        },
        async fetchSaldosLibro() {
            if (!this.form.month || !this.form.bank_account_id) {
                this.saldo_inicial = '';
                this.saldo_final = '';
                this.saldo_libro = '';
                return;
            }
            const res = await this.$http.get('/accounting/bank-book/preview', {
                params: {
                    month: this.form.month,
                    bank_account_id: this.form.bank_account_id
                }
            });
            this.saldo_inicial = res.data.saldo_inicial;
            this.saldo_final = res.data.saldo_final;
            this.saldo_libro = res.data.saldo_final; // Puedes ajustar según tu lógica
        },
        handleSelectAll(selection) {
            const pageIds = this.movimientos.map(m => Number(m.id));
            // Agrega los seleccionados de la página actual
            selection.forEach(row => {
                if (!this.selectedMovimientos.some(m => Number(m.id) === Number(row.id))) {
                    this.selectedMovimientos.push({ ...row });
                }
            });
            // Quita los deseleccionados de la página actual
            this.selectedMovimientos = this.selectedMovimientos.filter(
                m => !pageIds.includes(Number(m.id)) || selection.some(row => Number(row.id) === Number(m.id))
            );
            this.selectedMovimientosIds = this.selectedMovimientos.map(m => Number(m.id));
        },
        handleMovimientosPageChange(page) {
            this.movimientosPagination.current_page = page;
            this.onMonthOrAccountChange(page, false);
        },
        getRowKey(row) {
            return row.id;
        },
        restoreSelection() {
            this.$nextTick(() => {
                if (this.$refs.movimientosTable) {
                    this.$refs.movimientosTable.clearSelection();
                    this.movimientos.forEach(row => {
                        if (this.selectedMovimientos.some(m => Number(m.id) === Number(row.id))) {
                            this.$refs.movimientosTable.toggleRowSelection(row, true);
                        }
                    });
                }
            });
        },
        async onMonthOrAccountChange(page = 1, resetSelection = false) {
            await this.fetchSaldosLibro();
            if (resetSelection) {
                this.selectedMovimientosIds = [];
                this.selectedMovimientos = [];
            }
            if (page === undefined || page === null) page = 1;
            if (page === 1) this.movimientosPagination.current_page = 1;
            if (this.form.month && this.form.bank_account_id) {
                const res = await this.$http.get('/accounting/bank-reconciliation/movements', {
                    params: {
                        month: this.form.month,
                        bank_account_id: this.form.bank_account_id,
                        page: page,
                        per_page: this.movimientosPagination.per_page
                    }
                });

                this.movimientos = (res.data.movements || []).map(mov => ({
                    id: Number(mov.id),
                    date: mov.date,
                    documento: mov.document,
                    concepto: mov.description,
                    documento_tercero: mov.third_party_document || '',
                    nombre_tercero: mov.third_party_name || '',
                    numero_cheque: mov.cheque_number || '',
                    debit: mov.debit,
                    credit: mov.credit
                }));

                this.movimientosPagination = res.data.pagination || {
                    total: 0,
                    per_page: 5,
                    current_page: 1,
                    last_page: 1,
                };
                // Guarda los totales globales
                this.total_debit = res.data.totals ? Number(res.data.totals.debit) : 0;
                this.total_credit = res.data.totals ? Number(res.data.totals.credit) : 0;
                this.loadingMovimientos = false;
            } else {
                this.movimientos = [];
                this.movimientosPagination = {
                    total: 0,
                    per_page: 5,
                    current_page: 1,
                    last_page: 1,
                };
                this.total_debit = 0;
                this.total_credit = 0;
            }
            this.restoreSelection();
        },
        updateDiferencia() {
            const libro = parseFloat((this.saldo_libro + '').replace(/\./g, '').replace(',', '.')) || 0;
            const extracto = parseFloat((this.form.saldo_extracto + '').replace(/\./g, '').replace(',', '.')) || 0;
            this.form.diferencia = (libro - extracto).toLocaleString('es-CO', { minimumFractionDigits: 2 });
        },
        async submitForm() {
            // 1. Solicita todos los movimientos SIN paginación
            const res = await this.$http.get('/accounting/bank-reconciliation/movements', {
                params: {
                    month: this.form.month,
                    bank_account_id: this.form.bank_account_id,
                    page: 1,
                    per_page: 10000 // un número suficientemente grande para traer todos
                }
            });

            const todosLosMovimientos = (res.data.movements || []).map(mov => ({
                id: Number(mov.id),
                date: mov.date,
                documento: mov.document,
                concepto: mov.description,
                documento_tercero: mov.third_party_document || '',
                nombre_tercero: mov.third_party_name || '',
                numero_cheque: mov.cheque_number || '',
                debit: mov.debit,
                credit: mov.credit
            }));

            // 2. Pasa todos los movimientos y los seleccionados al segundo form
            this.datosConciliacion = {
                saldo_libro: this.saldo_libro,
                saldo_extracto: this.form.saldo_extracto,
                movimientos: todosLosMovimientos,
                movimientos_seleccionados: this.selectedMovimientos
            };
            this.showDialogReconciliation = true;
        },
        close() {
            this.$emit('update:showDialog', false);
            this.resetForm();
        },
        resetForm() {
            this.form = {
                date: moment().format('YYYY-MM-DD'),
                month: '',
                bank_account_id: null,
                saldo_extracto: '',
                diferencia: '',
            };
            this.saldo_inicial = '';
            this.saldo_final = '';
            this.saldo_libro = '';
            this.movimientos = [];
            this.movimientosPagination = {
                total: 0,
                per_page: 5,
                current_page: 1,
                last_page: 1,
            };
            this.selectedMovimientosIds = [];
            this.selectedMovimientos = [];
        }
    }
}
</script>