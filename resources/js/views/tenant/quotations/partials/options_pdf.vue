<template>
    <div>
        <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="clickClose"  width="30%">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 text-center font-weight-bold">
                    <p>Imprimir A4</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickToPrint('a4')">
                        <i class="fa fa-print"></i>
                    </button>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 text-center font-weight-bold">
                    <p>Imprimir Ticket</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickToPrint('ticket')">
                        <i class="fa fa-print"></i>
                    </button>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 text-center font-weight-bold">
                    <p>Imprimir A5</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickToPrint('a5')">
                        <i class="fa fa-print"></i>
                    </button>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 text-center font-weight-bold">
                    <p>Descargar A4</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload('a4')">
                        <i class="fa fa-download"></i>
                    </button>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 text-center font-weight-bold">
                    <p>Descargar Ticket</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload('ticket')">
                        <i class="fa fa-download"></i>
                    </button>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="form-group">
                        <label class="control-label">Enviar por WhatsApp</label>
                        <div class="input-group">
                            <el-input v-model="whatsapp_number" placeholder="Número WhatsApps">
                                <el-button slot="append" icon="el-icon-chat-dot-round" @click="clickSendWhatsapp" :loading="loadingWhatsapp">
                                    Enviar
                                </el-button>
                            </el-input>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="form-group">
                        <label class="control-label">Enviar por correo electrónico</label>
                        <div class="input-group">
                            <el-input v-model="customer_email" placeholder="Correo electrónico">
                                <el-button slot="append" icon="el-icon-message" @click="clickSendEmail" :loading="loading">
                                    Enviar
                                </el-button>
                            </el-input>
                        </div>
                    </div>
                </div>
            </div>

            <span slot="footer" class="dialog-footer">
<!-- <div class="col-md-6">
                    <el-input v-model="form.customer_email">
                        <el-button slot="append" icon="el-icon-message"   @click="clickSendEmail" :loading="loading">Enviar</el-button>
                    </el-input>
                </div> -->
                <!-- <div class="col-md-6">  -->
                <el-button @click="clickClose">Cerrar</el-button>
<!-- </div> -->

            </span>
        </el-dialog>


    </div>
</template>

<script>


    export default {

        props: ['showDialog', 'recordId', 'showClose'],
        data() {
            return {
                titleDialog: null,
                resource: 'quotations',
                form: {},
                loading: false,
                customer_email: "",
                whatsapp_number: "",
                loadingWhatsapp: false,
            }
        },
        created() {
            this.initForm()
        },
        methods: {
            initForm() {
                this.form = {
                    id: null,
                    external_id: null,
                }
            },
            create() {
                this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data
                        this.titleDialog = `Cotización registrada: ${this.form.identifier}`
                    })
            },
            clickClose() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            clickToPrint(format){
                window.open(`/${this.resource}/print/${this.form.external_id}/${format}`, '_blank');
            } ,
            clickDownload(format){
                window.open(`/${this.resource}/download/${this.form.external_id}/${format}`, '_blank');
            } ,

            clickSendEmail()
            {
                this.loading = true
                this.$http.post(`/${this.resource}/email`, {
                    customer_email: this.customer_email,
                    id: this.form.id,
                    customer_id: this.form.quotation.customer_id
                })
                .then(response => {
                    if (response.data.success) {
                        this.$message.success('El correo fue enviado satisfactoriamente')
                    } else {
                        this.$message.error('Error al enviar el correo')
                    }
                })
                .catch(error => {
                    this.$message.error('Error al enviar el correo')
                })
                .then(() => {
                    this.loading = false
                })
            },

            async getPdfAsBase64(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Error al obtener el PDF');
                    
                    const blob = await response.blob();
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result.split(',')[1]);
                        reader.readAsDataURL(blob);
                    });
                } catch (error) {
                    console.error('Error al convertir PDF a base64:', error);
                    throw error;
                }
            },

            async clickSendWhatsapp() {
                if (!this.whatsapp_number) {
                    this.$message.error('Ingrese un número de WhatsApp');
                    return;
                }

                this.loadingWhatsapp = true;
                
                try {
                    const pdfUrl = `/${this.resource}/print/${this.form.external_id}/a4`;
                    const pdfBase64 = await this.getPdfAsBase64(pdfUrl);

                    const payload = {
                        number: this.whatsapp_number,
                        message: `Cotización: ${this.form.identifier}`,
                        pdf_base64: pdfBase64,
                        filename: `cotizacion_${this.form.identifier}.pdf`
                    };

                    const { data } = await this.$http.post('/pos/whatsapp/send', payload);

                    if (data.success) {
                        this.$message.success('Cotización enviada por WhatsApp exitosamente');
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    this.$message.error(error.response?.data?.message || error.message);
                } finally {
                    this.loadingWhatsapp = false;
                }
            }
        }
    }
</script>
