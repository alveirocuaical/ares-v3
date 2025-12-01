<template>
    <el-dialog width="95%" title="Gestión de Resoluciones" :visible="showDialog" :close-on-click-modal="false"
        @close="close" append-to-body top="5vh">

        <div class="row">
            <!-- Resoluciones de la DIAN -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Resoluciones DIAN
                            <el-tooltip content="Seleccione una resolución para crearla en el sistema" placement="top">
                                <i class="el-icon-info" style="margin-left: 8px; cursor: pointer;"></i>
                            </el-tooltip>
                        </h5>
                        <div class="mt-2">
                            <el-button size="small" type="primary" :loading="loadingDian" @click="queryDianResolutions">
                                {{ dianButtonText }}
                            </el-button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <el-table ref="dianTable" :data="dianResolutions" style="width: 100%" height="400"
                            @selection-change="handleDianSelectionChange" @select="handleSingleSelection">
                            <el-table-column type="selection" width="55">
                            </el-table-column>
                            <el-table-column prop="ResolutionNumber" label="Número" width="150">
                            </el-table-column>
                            <el-table-column prop="Prefix" label="Prefijo" width="80">
                            </el-table-column>
                            <el-table-column prop="ResolutionDate" label="Fecha Resolución" width="120">
                            </el-table-column>
                            <el-table-column prop="ValidDateFrom" label="Válido Desde" width="120">
                            </el-table-column>
                            <el-table-column prop="ValidDateTo" label="Válido Hasta" width="120">
                            </el-table-column>
                            <el-table-column prop="FromNumber" label="Desde" width="100">
                            </el-table-column>
                            <el-table-column prop="ToNumber" label="Hasta" width="100">
                            </el-table-column>
                            <el-table-column label="Clave Técnica" width="150">
                                <template slot-scope="scope">
                                    <span v-if="scope.row.TechnicalKey && !scope.row.TechnicalKey._attributes">
                                        {{ scope.row.TechnicalKey.substring(0, 20) }}...
                                    </span>
                                    <span v-else class="text-muted">Sin clave</span>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </div>
            </div>

            <!-- Resoluciones del Sistema -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Resoluciones Sistema</h5>
                        <div class="mt-2">
                            <el-button size="small" type="primary" :loading="loadingSystemResolutions"
                                @click="refreshSystemResolutions">
                                Actualizar
                            </el-button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <el-table :data="systemResolutions" style="width: 100%" height="400">
                            <el-table-column prop="resolution_number" label="Número" width="150">
                            </el-table-column>
                            <el-table-column prop="prefix" label="Prefijo" width="80">
                            </el-table-column>
                            <el-table-column prop="name" label="Tipo" width="120">
                            </el-table-column>
                            <el-table-column prop="resolution_date" label="Fecha" width="120">
                            </el-table-column>
                            <el-table-column prop="from" label="Desde" width="100">
                            </el-table-column>
                            <el-table-column prop="to" label="Hasta" width="100">
                            </el-table-column>
                            <el-table-column label="Acciones" width="100">
                                <template slot-scope="scope">
                                    <el-button type="primary" size="mini" @click="editResolution(scope.row)">
                                        Editar
                                    </el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario para crear/editar resolución -->
        <div class="row mt-4" v-if="selectedDianResolutions.length > 0 || editingResolution">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            {{ editingResolution ? 'Editar Resolución' : 'Crear Resolución' }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveResolution">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.type_document_id }">
                                        <label class="control-label">Tipo de Documento *</label>
                                        <el-select v-model="form.type_document_id" placeholder="Seleccione tipo"
                                            :disabled="editingResolution || moduleType === 'eqdocs'">
                                            <el-option v-for="type in typeDocuments" :key="type.id" :label="type.name"
                                                :value="type.id">
                                            </el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.type_document_id"
                                            v-text="errors.type_document_id[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.resolution_number }">
                                        <label class="control-label">Número de Resolución *</label>
                                        <el-input v-model="form.resolution_number" :disabled="editingResolution">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.resolution_number"
                                            v-text="errors.resolution_number[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.prefix }">
                                        <label class="control-label">Prefijo *</label>
                                        <el-input v-model="form.prefix" :disabled="editingResolution">
                                        </el-input>
                                        <small class="form-control-feedback" v-if="errors.prefix"
                                            v-text="errors.prefix[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" :class="{ 'has-danger': errors.resolution_date }">
                                        <label class="control-label">Fecha Resolución *</label>
                                        <el-date-picker v-model="form.resolution_date" type="date"
                                            value-format="yyyy-MM-dd" placeholder="Fecha resolución"
                                            style="width: 100%">
                                        </el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.resolution_date"
                                            v-text="errors.resolution_date[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group" :class="{ 'has-danger': errors.resolution_date_end }">
                                        <label class="control-label">Fecha Resolución Hasta *</label>
                                        <el-date-picker v-model="form.resolution_date_end" type="date"
                                            value-format="yyyy-MM-dd" placeholder="Fecha hasta" style="width: 100%">
                                        </el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.resolution_date_end"
                                            v-text="errors.resolution_date_end[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos comunes -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.from }">
                                        <label class="control-label">Desde *</label>
                                        <el-input v-model="form.from"></el-input>
                                        <small class="form-control-feedback" v-if="errors.from"
                                            v-text="errors.from[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.to }">
                                        <label class="control-label">Hasta *</label>
                                        <el-input v-model="form.to"></el-input>
                                        <small class="form-control-feedback" v-if="errors.to"
                                            v-text="errors.to[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.generated }">
                                        <label class="control-label">Generadas</label>
                                        <el-input v-model="form.generated" placeholder="0"></el-input>
                                        <small class="form-control-feedback" v-if="errors.generated"
                                            v-text="errors.generated[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos específicos para facturación -->
                            <div v-if="moduleType !== 'eqdocs'" class="row">
                                <div class="col-md-8">
                                    <div class="form-group" :class="{ 'has-danger': errors.technical_key }">
                                        <label class="control-label">Clave Técnica *</label>
                                        <el-input v-model="form.technical_key"></el-input>
                                        <small class="form-control-feedback" v-if="errors.technical_key"
                                            v-text="errors.technical_key[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{ 'has-danger': errors.description }">
                                        <label class="control-label">Descripción</label>
                                        <el-input v-model="form.description"></el-input>
                                        <small class="form-control-feedback" v-if="errors.description"
                                            v-text="errors.description[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos específicos para documentos equivalentes (POS) -->
                            <div v-if="moduleType === 'eqdocs'">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" :class="{ 'has-danger': errors.date_from }">
                                            <label class="control-label">Fecha Desde</label>
                                            <el-date-picker v-model="form.date_from" type="date"
                                                value-format="yyyy-MM-dd" placeholder="Fecha inicial validez"
                                                style="width: 100%">
                                            </el-date-picker>
                                            <small class="form-control-feedback" v-if="errors.date_from"
                                                v-text="errors.date_from[0]"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group" :class="{ 'has-danger': errors.date_end }">
                                            <label class="control-label">Fecha Hasta</label>
                                            <el-date-picker v-model="form.date_end" type="date"
                                                value-format="yyyy-MM-dd" placeholder="Fecha final validez"
                                                style="width: 100%">
                                            </el-date-picker>
                                            <small class="form-control-feedback" v-if="errors.date_end"
                                                v-text="errors.date_end[0]"></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group" :class="{ 'has-danger': errors.electronic }">
                                            <label class="control-label">POS Electrónico</label><br>
                                            <el-checkbox v-model="form.electronic"></el-checkbox>
                                            <small class="form-control-feedback" v-if="errors.electronic"
                                                v-text="errors.electronic[0]"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-4" v-if="form.electronic">
                                        <div class="form-group" :class="{ 'has-danger': errors.plate_number }">
                                            <label class="control-label">Serial Caja</label>
                                            <el-input v-model="form.plate_number"
                                                placeholder="Número serial de la caja">
                                            </el-input>
                                            <small class="form-control-feedback" v-if="errors.plate_number"
                                                v-text="errors.plate_number[0]"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-5" v-if="form.electronic">
                                        <div class="form-group" :class="{ 'has-danger': errors.cash_type }">
                                            <label class="control-label">Tipo Caja</label>
                                            <el-input v-model="form.cash_type" placeholder="Tipo de caja">
                                            </el-input>
                                            <small class="form-control-feedback" v-if="errors.cash_type"
                                                v-text="errors.cash_type[0]"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración de establecimientos (común para ambos) -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" :class="{ 'has-danger': errors.show_in_establishments }">
                                        <label class="control-label">¿Dónde mostrar esta resolución?</label>
                                        <el-select v-model="form.show_in_establishments"
                                            placeholder="Seleccione una opción" style="width: 100%">
                                            <el-option label="Todos los establecimientos" value="all"></el-option>
                                            <el-option label="Ninguno" value="none"></el-option>
                                            <el-option label="Seleccionar" value="custom"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.show_in_establishments"
                                            v-text="errors.show_in_establishments[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-6" v-if="form.show_in_establishments === 'custom'">
                                    <div class="form-group" :class="{ 'has-danger': errors.establishment_ids }">
                                        <label class="control-label">Establecimientos</label>
                                        <el-select v-model="form.establishment_ids" multiple filterable
                                            placeholder="Seleccione los establecimientos" style="width: 100%">
                                            <el-option v-for="est in establishments" :key="est.id"
                                                :label="est.description" :value="est.id">
                                            </el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.establishment_ids"
                                            v-text="errors.establishment_ids[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions text-right">
                                <el-button @click="cancelForm">Cancelar</el-button>
                                <el-button type="primary" native-type="submit" :loading="loadingSave">
                                    {{ editingResolution ? 'Actualizar' : 'Crear' }}
                                </el-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div slot="footer" class="dialog-footer">
            <el-button @click="close">Cerrar</el-button>
        </div>
    </el-dialog>
</template>

<script>
export default {
    props: ['showDialog', 'moduleType'],

    data() {
        return {
            loadingDian: false,
            loadingSystemResolutions: false,
            loadingSave: false,
            dianResolutions: [],
            systemResolutions: [],
            selectedDianResolutions: [],
            editingResolution: null,
            errors: {},
            allTypeDocuments: {
                invoice: [
                    { id: 1, name: "Factura de Venta Nacional", code: '1' },
                    { id: 4, name: "Nota Crédito", code: '4' },
                    { id: 5, name: "Nota Débito", code: '5' },
                    { id: 11, name: "Documento Soporte Electrónico", code: '11' },
                    { id: 13, name: "Nota de Ajuste al Documento Soporte Electrónico", code: '13' }
                ],
                eqdocs: [
                    { id: 15, name: "Documento Equivalente", code: '15' },
                    { id: 26, name: "Nota de crédito al Documento Equivalente", code: '26' }
                ]
            },
            form: {
                id: null,
                type_document_id: null,
                resolution_number: null,
                prefix: null,
                resolution_date: null,
                resolution_date_end: null,
                technical_key: null,
                from: null,
                to: null,
                generated: '0',
                description: null,
                show_in_establishments: 'all',
                establishment_ids: [],
                date_from: null,
                date_end: null,
                electronic: true,
                plate_number: null,
                cash_type: null,
            }
        }
    },
    async mounted() {
        await this.loadEstablishments();
    },
    created() {
        if (this.showDialog) {
            this.refreshSystemResolutions();
        }
    },
    computed: {
        typeDocuments() {
            return this.allTypeDocuments[this.moduleType] || this.allTypeDocuments.invoice;
        },

        modalTitle() {
            const titles = {
                invoice: 'Gestión de Resoluciones - Facturación',
                eqdocs: 'Gestión de Resoluciones - Documentos Equivalentes'
            };
            return titles[this.moduleType] || 'Gestión de Resoluciones';
        },

        dianButtonText() {
            const texts = {
                invoice: 'Consultar DIAN - Facturación',
                eqdocs: 'Consultar DIAN - Documentos Equivalentes'
            };
            return texts[this.moduleType] || 'Consultar DIAN';
        }
    },
    methods: {
        async loadEstablishments() {
            try {
                const response = await this.$http.get('/establishments/records');
                this.establishments = response.data.data || [];
            } catch (error) {
                console.error('Error loading establishments:', error);
            }
        },
        clearData() {
            this.dianResolutions = [];
            this.systemResolutions = [];
            this.selectedDianResolutions = [];
            this.editingResolution = null;
            this.errors = {};
            this.cancelForm();
        },
        async queryDianResolutions() {
            this.loadingDian = true;
            try {
                const response = await this.$http.post('/co-configuration/production/queryTechnicalKey', {
                    type: this.moduleType || 'invoice'
                });

                if (response.data.success) {
                    this.dianResolutions = response.data.resolutions || [];
                    this.$message.success(response.data.message);
                } else {
                    this.$message.error(response.data.message || 'Error al consultar resoluciones');
                }
            } catch (error) {
                this.$message.error('Error al consultar resoluciones de la DIAN');
                console.error(error);
            } finally {
                this.loadingDian = false;
            }
        },

        async refreshSystemResolutions() {
            this.loadingSystemResolutions = true;
            try {
                let endpoint;
                if (this.moduleType === 'eqdocs') {
                    endpoint = '/pos/records';
                } else {
                    endpoint = `/client/configuration/co_type_documents?module=${this.moduleType}`;
                }

                const response = await this.$http.get(endpoint);

                if (this.moduleType === 'eqdocs') {
                    this.systemResolutions = response.data.data || [];
                } else {
                    this.systemResolutions = response.data.data || [];
                }

            } catch (error) {
                console.error('Error al cargar resoluciones del sistema:', error);
                this.$message.error('Error al cargar resoluciones del sistema');
            } finally {
                this.loadingSystemResolutions = false;
            }
        },

        handleDianSelectionChange(selection) {
            this.selectedDianResolutions = selection;
            if (selection.length > 0) {
                const selected = selection[0];

                let defaultTypeId = null;
                if (this.moduleType === 'eqdocs') {
                    defaultTypeId = 15;
                } else {
                    defaultTypeId = 1;
                }

                this.form = {
                    id: null,
                    type_document_id: defaultTypeId,
                    resolution_number: selected.ResolutionNumber,
                    prefix: selected.Prefix,
                    resolution_date: selected.ResolutionDate,
                    resolution_date_end: selected.ValidDateTo,
                    technical_key: selected.TechnicalKey || '',
                    from: selected.FromNumber,
                    to: selected.ToNumber,
                    generated: selected.FromNumber - 1,
                    description: `Resolución ${selected.ResolutionNumber}`,
                    show_in_establishments: 'all',
                    establishment_ids: [],
                    date_from: selected.ValidDateFrom || null,
                    date_end: selected.ValidDateTo || null,
                    electronic: true,
                    plate_number: null,
                    cash_type: null,
                };
                this.editingResolution = null;
            }
        },

        editResolution(resolution) {
            this.editingResolution = resolution;
            this.selectedDianResolutions = [];

            const typeDoc = this.typeDocuments.find(t => t.code == resolution.code);

            this.form = {
                id: resolution.id,
                type_document_id: typeDoc ? typeDoc.id : null,
                resolution_number: resolution.resolution_number,
                prefix: resolution.prefix,
                resolution_date: resolution.resolution_date,
                resolution_date_end: resolution.resolution_date_end,
                technical_key: resolution.technical_key,
                from: resolution.from,
                to: resolution.to,
                generated: resolution.generated || '0',
                description: resolution.description,
                show_in_establishments: resolution.show_in_establishments || 'all',
                establishment_ids: resolution.establishment_ids ?
                    (Array.isArray(resolution.establishment_ids) ?
                        resolution.establishment_ids :
                        JSON.parse(resolution.establishment_ids || '[]')) : [],
                date_from: resolution.date_from || null,
                date_end: resolution.date_end || null,
                electronic: resolution.electronic || false,
                plate_number: resolution.plate_number || null,
                cash_type: resolution.cash_type || null,
            };
        },

        async saveResolution() {
            this.loadingSave = true;
            this.errors = {};

            if (!this.form.show_in_establishments) {
                this.form.show_in_establishments = 'all';
            }

            try {
                let response;
                if (this.editingResolution) {
                    if (this.moduleType === 'eqdocs') {
                        response = await this.$http.post(`/pos/configuration`, this.form);
                    } else {
                        response = await this.$http.post(`/configuration/type_document/${this.form.id}`, this.form);
                    }
                } else {
                    // Crear nueva resolución
                    if (this.moduleType === 'eqdocs') {
                        response = await this.$http.post('/pos/configuration', this.form);
                    } else {
                        const formData = {
                            ...this.form,
                            module_type: this.moduleType
                        };
                        response = await this.$http.post('/client/configuration/storeResolutionFromModal', formData);
                    }
                }

                if (response.data.success) {
                    this.$message.success(response.data.message);
                    this.refreshSystemResolutions();
                    this.cancelForm();
                    this.$emit('refresh');
                } else {
                    this.$message.error(response.data.message);
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data;
                } else {
                    this.$message.error('Error al guardar la resolución');
                    console.error(error);
                }
            } finally {
                this.loadingSave = false;
            }
        },
        cancelForm() {
            this.selectedDianResolutions = [];
            this.editingResolution = null;
            this.form = {
                id: null,
                type_document_id: null,
                resolution_number: null,
                prefix: null,
                resolution_date: null,
                resolution_date_end: null,
                technical_key: null,
                from: null,
                to: null,
                generated: '0',
                description: null,
                show_in_establishments: 'all',
                establishment_ids: [],
                date_from: null,
                date_end: null,
                electronic: true,
                plate_number: null,
                cash_type: null,
            };
            this.errors = {};
        },

        handleSingleSelection(selection, row) {
            this.$refs.dianTable.clearSelection();
            this.$nextTick(() => {
                this.$refs.dianTable.toggleRowSelection(row, true);
            });
        },

        close() {
            this.cancelForm();
            this.$emit('update:showDialog', false);
        }
    },
    watch: {
        moduleType: {
            handler(newValue, oldValue) {
                if (newValue !== oldValue) {
                    this.clearData();
                    this.refreshSystemResolutions();
                }
            },
            immediate: false
        },

        showDialog: {
            handler(newValue) {
                if (newValue) {
                    this.clearData();
                    this.refreshSystemResolutions();
                }
            },
            immediate: true
        }
    },
}
</script>

<style scoped>
.card {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.card-header {
    padding: 0.75rem 1.25rem;
    margin-bottom: 0;
    border-bottom: 1px solid rgba(0, 0, 0, .125);
    border-top-left-radius: calc(0.375rem - 1px);
    border-top-right-radius: calc(0.375rem - 1px);
}

.card-body {
    flex: 1 1 auto;
    padding: 1.25rem;
}

.form-control-feedback {
    color: #dc3545;
    font-size: 0.875rem;
}

.has-danger .el-input__inner {
    border-color: #dc3545;
}
</style>