<template>
    <el-dialog :visible="showDialog" title="Conciliación - Detalle" width="80%" @close="close">
        <el-form label-width="150px">
            <div class="row mb-3">
                <div class="col-md-4">
                    <el-form-item label="Saldo Libro">
                        <el-input :value="saldo_libro" readonly></el-input>
                    </el-form-item>
                </div>
                <div class="col-md-4">
                    <el-form-item label="Saldo Extracto">
                        <el-input :value="saldo_extracto" readonly></el-input>
                    </el-form-item>
                </div>
                <div class="col-md-4">
                    <el-form-item label="Diferencia">
                        <el-input :value="diferenciaFinal" readonly></el-input>
                    </el-form-item>
                </div>
            </div>
        </el-form>

        <div class="mb-4">
            <h4>Tabla Más (Créditos no seleccionados)</h4>
            <el-table :data="tablaMas" border style="width: 100%">
                <el-table-column prop="date" label="Fecha" width="110"></el-table-column>
                <el-table-column prop="nombre_tercero" label="Nombre del tercero"></el-table-column>
                <el-table-column prop="origen" label="Origen"></el-table-column>
                <el-table-column prop="n_soporte" label="N de soporte"></el-table-column>
                <el-table-column prop="cheque" label="Cheque"></el-table-column>
                <el-table-column prop="concepto" label="Concepto"></el-table-column>
                <el-table-column
                    prop="valor"
                    label="Valor"
                    width="120"
                    :formatter="row => formatValor(row.credit)"
                ></el-table-column>
            </el-table>
            <div class="text-right mt-2">
                <strong>Total Más: {{ totalMas | currency }}</strong>
            </div>
            <el-button type="primary" @click="showAddDetail = true">Agregar detalle manual</el-button>
        </div>

        <div>
            <h4>Tabla Menos (Débitos no seleccionados)</h4>
            <el-table :data="tablaMenos" border style="width: 100%">
                <el-table-column prop="date" label="Fecha" width="110"></el-table-column>
                <el-table-column prop="nombre_tercero" label="Nombre del tercero"></el-table-column>
                <el-table-column prop="origen" label="Origen"></el-table-column>
                <el-table-column prop="n_soporte" label="N de soporte"></el-table-column>
                <el-table-column prop="cheque" label="Cheque"></el-table-column>
                <el-table-column prop="concepto" label="Concepto"></el-table-column>
                <el-table-column
                    prop="valor"
                    label="Valor"
                    width="120"
                    :formatter="row => formatValor(row.debit)"
                ></el-table-column>
            </el-table>
            <div class="text-right mt-2">
                <strong>Total Menos: {{ totalMenos | currency }}</strong>
            </div>
            <el-button type="primary" @click="showAddDetail = true">Agregar detalle manual</el-button>
        </div>

        <span slot="footer" class="dialog-footer">
            <el-button @click="close">Cerrar</el-button>
        </span>
    </el-dialog>
</template>

<script>
export default {
    props: {
        showDialog: { type: Boolean, required: true },
        saldo_libro: { type: [String, Number], required: true },
        saldo_extracto: { type: [String, Number], required: true },
        movimientos: { type: Array, required: true },
        movimientosSeleccionados: { type: Array, required: true },
    },
    data() {
        return {
            tablaMas: [],
            tablaMenos: [],
        }
    },
    computed: {
        totalMas() {
            return this.tablaMas.reduce((sum, m) => sum + this.parseNumber(m.credit), 0);
        },
        totalMenos() {
            return this.tablaMenos.reduce((sum, m) => sum + this.parseNumber(m.debit), 0);
        },
        diferenciaFinal() {
            // Diferencia original + totalMas - totalMenos
            const libro = this.parseNumber(this.saldo_libro);
            const extracto = this.parseNumber(this.saldo_extracto);
            const diferenciaBase = libro - extracto;
            const diferencia = diferenciaBase + this.totalMas - this.totalMenos;
            return diferencia.toLocaleString('es-CO', { minimumFractionDigits: 2 });
        }
    },
    watch: {
        showDialog(val) {
            if (val) this.procesarTablas();
        },
        movimientos() {
            this.procesarTablas();
        },
        movimientosSeleccionados() {
            this.procesarTablas();
        }
    },
    methods: {
        procesarTablas() {
            const seleccionadosIds = this.movimientosSeleccionados.map(m => Number(m.id));
            // Filtra los NO seleccionados
            const noSeleccionados = this.movimientos.filter(m => !seleccionadosIds.includes(Number(m.id)));
            // Mapea los campos requeridos para las tablas
            this.tablaMas = noSeleccionados
                .filter(m =>
                    this.parseNumber(m.debit) === 0 &&
                    this.parseNumber(m.credit) > 0
                )
                .map(m => ({
                    ...m,
                    origen: '',
                    n_soporte: '',
                    cheque: '',
                    concepto: m.concepto || m.description || '',
                    nombre_tercero: m.nombre_tercero || m.third_party_name || '',
                    date: m.date || '',
                }));
            this.tablaMenos = noSeleccionados
                .filter(m =>
                    this.parseNumber(m.credit) === 0 &&
                    this.parseNumber(m.debit) > 0
                )
                .map(m => ({
                    ...m,
                    origen: '',
                    n_soporte: '',
                    cheque: '',
                    concepto: m.concepto || m.description || '',
                    nombre_tercero: m.nombre_tercero || m.third_party_name || '',
                    date: m.date || '',
                }));
        },
        parseNumber(val) {
            if (typeof val === 'number') return val;
            if (!val) return 0;
            return parseFloat((val + '').replace(/\./g, '').replace(',', '.')) || 0;
        },
        formatValor(val) {
            return this.parseNumber(val).toLocaleString('es-CO', { minimumFractionDigits: 2 });
        },
        close() {
            this.$emit('update:showDialog', false);
        }
    },
    filters: {
        currency(val) {
            return Number(val).toLocaleString('es-CO', { minimumFractionDigits: 2 });
        }
    }
}
</script>