import React, { useState, useEffect } from "react";
import { PDFDocument } from "pdf-lib";
import { pdfjs } from "react-pdf";
import { Document, Page } from "react-pdf";
import i18next from "i18next";
import { useParams } from "react-router-dom";
import { Base64 } from "js-base64";
import * as yup from "yup";
import { useFormik } from "formik";
import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import Form from "react-bootstrap/Form";
import "react-pdf/dist/Page/AnnotationLayer.css";
import "react-pdf/dist/Page/TextLayer.css";
import { objectToFormData } from "../Utils/common.utils";

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
    "pdfjs-dist/build/pdf.worker.min.js",
    import.meta.url
).toString();

const InsuranceForm = () => {
    const [show, setShow] = useState(false);

    const [pdfForm, setPdfForm] = useState(null);
    const [pdfDoc, setPdfDoc] = useState(null);
    const [pdfData, setPdfData] = useState(null);

    const initialValues = {
        // page 1
        type: "New",
        AgentName: "",
        AgentNo: "",
        CompanyName: "",
        CompanyNo: "",
        AgreementNo: "",
        IDNumber: "",
        FirstName: "",
        LastName: "",
        ZipCode: "",
        Town: "",
        HouseNumber: "",
        Street: "",
        Email: "",
        CellphoneNo: "",
        TelephoneNo: "",
        canFirstName: "",
        canLastName: "",
        canPassport: "",
        canOrigin: "",
        canDOB: "",
        canFirstDateOfIns: "",
        canZipcode: "",
        canTown: "",
        canHouseNo: "",
        canStreet: "",
        canTelephone: "",
        canCellPhone: "",
        canEmail: "",
        periodTo: "",
        periodFrom: "",
        prevTo: "",
        prevFrom: "",
        prevCompanyname: "",
        prevPolicy: "",
        prevMemberShip: "",
        prevInsurance: "No",
        occupasion: "other",
        gender: "male",
        // page 2
        FFirstName: "",
        FLastName: "",
        FPasswordno: "",
        FPId: "",
        FPFirstName: "",
        FPLastName: "",
        FPZipCode: "",
        FPtown: "",
        FPhouseNo: "",
        FPstreet: "",
        FPexpDate: "",
        FPcardNo: "",
        FPcellphone: "",
        FPemail: "",
        FPdate: "",
        employerdate: "",
        employername: "",
        Months: "6Months",
        sixMonthPayment: "",
        twelveMonthsPayment: "",
        // page 3
        GFirstname: "",
        GLastname: "",
        GPassportno: "",
        GDetails: "",
        GCandidatename: "",
        GDate: "",
        // page 4
        Hname: "",
        canPassportNo: "",
        canName: "",
        canDate: "",
    };

    const [formValues, setFormValues] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);

    const params = useParams();
    const id = Base64.decode(params.id);

    useEffect(() => {
        const fetchPdf = async () => {
            const formPdfBytes = await fetch("/pdfs/health-insurance.pdf").then(
                (res) => res.arrayBuffer()
            );
            const PdfDoc = await PDFDocument.load(formPdfBytes);
            setPdfDoc(PdfDoc);
            const form = PdfDoc.getForm();
            setPdfForm(form);
            const allFields = form.getFields();
            for (let index = 0; index < allFields.length; index++) {
                const element = allFields[index];
                // console.log(element.getName());
            }
        };
        fetchPdf();
    }, []);

    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
        isSubmitting,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        // validationSchema: formSchema,
        onSubmit: async (values) => {
            console.log(values);
            await saveFormData(true);
        },
    });

    const saveFormData = async (isSubmit) => {
        pdfForm.getRadioGroup("Type").select(values.type);
        pdfForm.getTextField("AgentName").setText(values.AgentName);
        pdfForm.getTextField("AgentNo").setText(values.AgentNo);
        pdfForm.getTextField("CompanyName").setText(values.CompanyName);
        pdfForm.getTextField("CompanyNo").setText(values.CompanyNo);
        pdfForm.getTextField("AgreementNo").setText(values.AgreementNo);
        pdfForm.getTextField("IDNumber").setText(values.IDNumber);
        pdfForm.getTextField("FirstName").setText(values.FirstName);
        pdfForm.getTextField("LastName").setText(values.LastName);
        pdfForm.getTextField("ZipCode").setText(values.ZipCode);
        pdfForm.getTextField("Town").setText(values.Town);
        pdfForm.getTextField("HouseNumber").setText(values.HouseNumber);
        pdfForm.getTextField("Street").setText(values.Street);
        pdfForm.getTextField("Email").setText(values.Email);
        pdfForm.getTextField("CellphoneNo").setText(values.CellphoneNo);
        pdfForm.getTextField("TelephoneNo").setText(values.TelephoneNo);
        pdfForm.getTextField("canFirstName").setText(values.canFirstName);
        pdfForm.getTextField("canLastName").setText(values.canLastName);
        pdfForm.getTextField("canPassport").setText(values.canPassport);
        pdfForm.getTextField("canOrigin").setText(values.canOrigin);
        pdfForm.getTextField("canDOB").setText(values.canDOB);
        pdfForm
            .getTextField("canFirstDateOfIns")
            .setText(values.canFirstDateOfIns);
        pdfForm.getTextField("canZipcode").setText(values.canZipcode);
        pdfForm.getTextField("canTown").setText(values.canTown);
        pdfForm.getTextField("canHouseNo").setText(values.canHouseNo);
        pdfForm.getTextField("canStreet").setText(values.canStreet);
        pdfForm.getTextField("canTelephone").setText(values.canTelephone);
        pdfForm.getTextField("canCellPhone").setText(values.canCellPhone);
        pdfForm.getTextField("canEmail").setText(values.canEmail);
        pdfForm.getTextField("periodTo").setText(values.periodTo);
        pdfForm.getTextField("periodFrom").setText(values.periodFrom);
        pdfForm.getTextField("prevTo").setText(values.prevTo);
        pdfForm.getTextField("prevFrom").setText(values.prevFrom);
        pdfForm.getTextField("prevCompanyname").setText(values.prevCompanyname);
        pdfForm.getTextField("prevPolicy").setText(values.prevPolicy);
        pdfForm.getTextField("prevMemberShip").setText(values.prevMemberShip);
        pdfForm.getRadioGroup("prevInsurance").select(values.prevInsurance);
        pdfForm.getRadioGroup("occupasion").select(values.occupasion);
        pdfForm.getRadioGroup("gender").select(values.gender);

        pdfForm.getRadioGroup("Months").select(values.Months);
        pdfForm.getTextField("sixMonthPayment").setText(values.sixMonthPayment);
        pdfForm
            .getTextField("twelveMonthsPayment")
            .setText(values.twelveMonthsPayment);
        pdfForm.getTextField("F-FirstName").setText(values.FFirstName);
        pdfForm.getTextField("F-LastName").setText(values.FLastName);
        pdfForm.getTextField("F-Passwordno").setText(values.FPasswordno);
        pdfForm.getTextField("F-P-Id").setText(values.FPId);
        pdfForm.getTextField("F-P-FirstName").setText(values.FPFirstName);
        pdfForm.getTextField("F-P-LastName").setText(values.FPLastName);
        pdfForm.getTextField("F-P-ZipCode").setText(values.FPZipCode);
        pdfForm.getTextField("F-P-town").setText(values.FPtown);
        pdfForm.getTextField("F-P-houseNo").setText(values.FPhouseNo);
        pdfForm.getTextField("F-P-street").setText(values.FPstreet);
        pdfForm.getTextField("F-P-expDate").setText(values.FPexpDate);
        pdfForm.getTextField("F-P-cardNo").setText(values.FPcardNo);
        pdfForm.getTextField("F-P-cellphone").setText(values.FPcellphone);
        pdfForm.getTextField("F-P-email").setText(values.FPemail);
        pdfForm.getTextField("F-P-date").setText(values.FPdate);
        pdfForm.getTextField("employer-name").setText(values.employername);
        pdfForm.getTextField("employer-date").setText(values.employerdate);

        pdfForm.getTextField("G-firstname").setText(values.GFirstname);
        pdfForm.getTextField("G-lastname").setText(values.GLastname);
        pdfForm.getTextField("G-passportno").setText(values.GPassportno);
        pdfForm.getTextField("G-details").setText(values.GDetails);
        pdfForm.getTextField("G-candidatename").setText(values.GCandidatename);
        pdfForm.getTextField("G-date").setText(values.GDate);
        pdfForm.getTextField("H-name").setText(values.Hname);
        pdfForm
            .getTextField("candidate-passport-no")
            .setText(values.canPassportNo);
        pdfForm.getTextField("candidate-name").setText(values.canName);
        pdfForm.getTextField("candidate-date").setText(values.canDate);

        const pdfBytes = await pdfDoc.save();
        const blob = new Blob([pdfBytes], { type: "application/pdf" });
        const url = URL.createObjectURL(blob);

        if (!isSubmit) {
            setPdfData(url);
            setShow(true);
        } else {
            // Convert JSON object to FormData
            let formData = objectToFormData(values);
            formData.append("pdf_file", blob);

            await axios
                .post(`/api/worker/${id}/insurance-form`, formData, {
                    headers: {
                        Accept: "application/json, text/plain, */*",
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((res) => {
                    alert.success("Successfuly signed");
                    setTimeout(() => {
                        window.location.reload(true);
                    }, 2000);
                })
                .catch((e) => {
                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                });
        }
        // console.log(pdfBytes, "arrayBytes");
        console.log(blob, "blob");
    };

    // const handleSubmit = async () => {
    //     await saveFormData(true);
    // };

    const handleShow = async () => {
        await saveFormData(false);
    };

    const handleClose = () => setShow(false);

    const getForm = async () => {
        await axios.get(`/api/worker/${id}/insurance-form`).then((res) => {
            i18next.changeLanguage(res.data.lng);
            if (res.data.lng == "heb") {
                import("../Assets/css/rtl.css");
                document.querySelector("html").setAttribute("dir", "rtl");
            } else {
                document.querySelector("html").removeAttribute("dir");
            }

            if (res.data.form) {
                setFormValues(res.data.form.data);
                if (res.data.form.submitted_at) {
                    setTimeout(() => {
                        disableInputs();
                    }, 2000);
                    setIsSubmitted(true);
                }
            } else if (res.data.worker) {
                const _worker = res.data.worker;
                setFieldValue("IDNumber", _worker.worker_id);
                setFieldValue("FirstName", _worker.firstname);
                setFieldValue("LastName", _worker.lastname);
                setFieldValue("Email", _worker.email);
                setFieldValue("CellphoneNo", _worker.phone);
                setFieldValue("canFirstName", _worker.firstname);
                setFieldValue("canLastName", _worker.lastname);
                setFieldValue("canEmail", _worker.email);
                setFieldValue("canCellPhone", _worker.phone);

                const _gender = _worker.gender;
                setFieldValue(
                    "gender",
                    _gender.charAt(0).toUpperCase() + _gender.slice(1)
                );
                setFieldValue("FFirstName", _worker.firstname);
                setFieldValue("FLastName", _worker.lastname);
                setFieldValue("GFirstname", _worker.firstname);
                setFieldValue("GLastname", _worker.lastname);
            }
        });
    };

    const disableInputs = () => {
        // Disable inputs within the div with the id "targetDiv"
        const inputs = document.querySelectorAll("input ");
        inputs.forEach((input) => {
            input.disabled = true;
        });
        const selects = document.querySelectorAll("select");
        selects.forEach((select) => {
            select.disabled = true;
        });
    };

    useEffect(() => {
        getForm();
    }, []);

    useEffect(() => {
        console.log(values.Months);
        if (values.Months == "6Months") {
            setFieldValue("twelveMonthsPayment", "");
        } else {
            setFieldValue("sixMonthPayment", "");
        }
    }, [values.Months]);

    return (
        <div className="my-2">
            <div className="row justify-content-center">
                <div className="col-md-8">
                    <label className="control-label">Type</label>
                    <Form.Check
                        label="a new candidate"
                        name="type"
                        checked={values.type === "New"}
                        value={"New"}
                        type="radio"
                        id={`inline-1`}
                        onChange={handleChange}
                    />
                    <Form.Check
                        label="renewal/extention"
                        name="type"
                        checked={values.type === "Renewal"}
                        value={"Renewal"}
                        type="radio"
                        id={`inline-2`}
                        onChange={handleChange}
                    />
                </div>
            </div>
            <br />
            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Agent Name</label>
                        <input
                            type="text"
                            name={"AgentName"}
                            className="form-control"
                            value={values.AgentName}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Agent Number</label>
                        <input
                            type="text"
                            name={"AgentNo"}
                            className="form-control"
                            value={values.AgentNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>
            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">CompanyName</label>
                        <input
                            type="text"
                            name={"CompanyName"}
                            className="form-control"
                            value={values.CompanyName}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">CompanyNo</label>
                        <input
                            type="text"
                            name={"CompanyNo"}
                            className="form-control"
                            value={values.CompanyNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>
            <div className="row justify-content-center">
                <div className="col-md-8">
                    <div className="form-group">
                        <label className="control-label">AgreementNo</label>
                        <input
                            type="text"
                            name={"AgreementNo"}
                            className="form-control"
                            value={values.AgreementNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Details of policyholder
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">ID Number</label>
                        <input
                            type="text"
                            name={"IDNumber"}
                            className="form-control"
                            value={values.IDNumber}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            name={"FirstName"}
                            className="form-control"
                            value={values.FirstName}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            name={"LastName"}
                            className="form-control"
                            value={values.LastName}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Zip Code</label>
                        <input
                            type="text"
                            name={"ZipCode"}
                            className="form-control"
                            value={values.ZipCode}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Town</label>
                        <input
                            type="text"
                            name={"Town"}
                            className="form-control"
                            value={values.Town}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">House Number</label>
                        <input
                            type="text"
                            name={"HouseNumber"}
                            className="form-control"
                            value={values.HouseNumber}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Street</label>
                        <input
                            type="text"
                            name={"Street"}
                            className="form-control"
                            value={values.Street}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Email</label>
                        <input
                            type="text"
                            name={"Email"}
                            className="form-control"
                            value={values.Email}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Cellphone Number
                        </label>
                        <input
                            type="text"
                            name={"CellphoneNo"}
                            className="form-control"
                            value={values.CellphoneNo}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Telephone Number
                        </label>
                        <input
                            type="text"
                            name={"TelephoneNo"}
                            className="form-control"
                            value={values.TelephoneNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Insurance Candidate details
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            name={"canFirstName"}
                            className="form-control"
                            value={values.canFirstName}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            name={"canLastName"}
                            className="form-control"
                            value={values.canLastName}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Passport</label>
                        <input
                            type="text"
                            name={"canPassport"}
                            className="form-control"
                            value={values.canPassport}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Origin</label>
                        <input
                            type="text"
                            name={"canOrigin"}
                            className="form-control"
                            value={values.canOrigin}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date of Birth</label>
                        <input
                            type="date"
                            name={"canDOB"}
                            className="form-control"
                            value={values.canDOB}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            First Date of Insurance
                        </label>
                        <input
                            type="date"
                            name={"canFirstDateOfIns"}
                            className="form-control"
                            value={values.canFirstDateOfIns}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Zip Code</label>
                        <input
                            type="text"
                            name={"canZipcode"}
                            className="form-control"
                            value={values.canZipcode}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Town</label>
                        <input
                            type="text"
                            name={"canTown"}
                            className="form-control"
                            value={values.canTown}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">House Number</label>
                        <input
                            type="text"
                            name={"canHouseNo"}
                            className="form-control"
                            value={values.canHouseNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Street</label>
                        <input
                            type="text"
                            name={"canStreet"}
                            className="form-control"
                            value={values.canStreet}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Telephone</label>
                        <input
                            type="text"
                            name={"canTelephone"}
                            className="form-control"
                            value={values.canTelephone}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Cellphone</label>
                        <input
                            type="text"
                            name={"canCellPhone"}
                            className="form-control"
                            value={values.canCellPhone}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Email</label>
                        <input
                            type="text"
                            name={"canEmail"}
                            className="form-control"
                            value={values.canEmail}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <label className="control-label">Gender</label>
                    <Form.Check
                        label="Male"
                        name="gender"
                        value="Male"
                        checked={values.gender === "Male"}
                        type="radio"
                        id={`gender-1`}
                        onChange={handleChange}
                        disabled
                    />
                    <Form.Check
                        label="Female"
                        name="gender"
                        value="Female"
                        checked={values.gender === "Female"}
                        type="radio"
                        onChange={handleChange}
                        id={`gender-2`}
                        disabled
                    />
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Insurance Candidate's requested
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Period To</label>
                        <input
                            type="text"
                            name="periodTo"
                            className="form-control"
                            value={values.periodTo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Period From</label>
                        <input
                            type="text"
                            name="periodFrom"
                            className="form-control"
                            value={values.periodFrom}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Insurance Candidate's occupation
            </div>

            <div className="row justify-content-center">
                <div className="col-md-8">
                    <Form.Check
                        label="nursing"
                        name="occupasion"
                        value="nursing"
                        checked={values.occupasion === "nursing"}
                        type="radio"
                        id={`occupasion-1`}
                        onChange={handleChange}
                    />
                    <Form.Check
                        label="agriculture"
                        name="occupasion"
                        value="agriculture"
                        checked={values.occupasion === "agriculture"}
                        type="radio"
                        id={`occupasion-2`}
                        onChange={handleChange}
                    />
                    <Form.Check
                        label="construction"
                        name="occupasion"
                        value="construction"
                        checked={values.occupasion === "construction"}
                        type="radio"
                        id={`occupasion-3`}
                        onChange={handleChange}
                    />
                    <Form.Check
                        label="other"
                        name="occupasion"
                        value="other"
                        checked={values.occupasion === "other"}
                        type="radio"
                        id={`occupasion-4`}
                        onChange={handleChange}
                    />
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Details of previous insurance policies
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Insurance Period To
                        </label>
                        <input
                            type="text"
                            name="prevTo"
                            className="form-control"
                            value={values.prevTo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Insurance Period From
                        </label>
                        <input
                            type="text"
                            name="prevFrom"
                            className="form-control"
                            value={values.prevFrom}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Company Name</label>
                        <input
                            type="text"
                            name="prevCompanyname"
                            className="form-control"
                            value={values.prevCompanyname}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Policy Number</label>
                        <input
                            type="text"
                            name="prevPolicy"
                            className="form-control"
                            value={values.prevPolicy}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <label className="control-label">
                        Have you ever insurance from previous Company?
                    </label>
                    <Form.Check
                        label="Yes"
                        name="prevInsurance"
                        checked={values.prevInsurance === "Yes"}
                        type="radio"
                        id={`prevInsurance-1`}
                        onChange={handleChange}
                    />
                    <Form.Check
                        label="No"
                        name="prevInsurance"
                        checked={values.prevInsurance === "No"}
                        type="radio"
                        onChange={handleChange}
                        id={`prevInsurance-2`}
                    />
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Membership Number
                        </label>
                        <input
                            type="text"
                            name="prevMemberShip"
                            className="form-control"
                            value={values.prevMemberShip}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Payment By Credit Card
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <Form.Check
                        label="6 Months"
                        name="Months"
                        value={"6Months"}
                        checked={values.Months === "6Months"}
                        type="radio"
                        id={`Months-1`}
                        onChange={handleChange}
                    />
                </div>
                <div
                    className="col-md-4"
                    style={
                        values.Months === "12Months"
                            ? { pointerEvents: "none", opacity: 0.5 }
                            : {}
                    }
                >
                    <div className="form-group">
                        <label className="control-label">
                            Six Month Payment No.
                        </label>
                        <input
                            type="text"
                            name="sixMonthPayment"
                            className="form-control"
                            value={values.sixMonthPayment}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <Form.Check
                        label="12 Months"
                        name="Months"
                        value={"12Months"}
                        checked={values.Months === "12Months"}
                        type="radio"
                        id={`Months-2`}
                        onChange={handleChange}
                    />
                </div>
                <div
                    className="col-md-4"
                    style={
                        values.Months === "6Months"
                            ? { pointerEvents: "none", opacity: 0.5 }
                            : {}
                    }
                >
                    <div className="form-group">
                        <label className="control-label">
                            Twelve Month Payment No.
                        </label>
                        <input
                            type="text"
                            name="twelveMonthsPayment"
                            className="form-control"
                            value={values.twelveMonthsPayment}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            name="FFirstName"
                            className="form-control"
                            value={values.FFirstName}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            name="FLastName"
                            className="form-control"
                            value={values.FLastName}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-8">
                    <div className="form-group">
                        <label className="control-label">Passport Number</label>
                        <input
                            type="text"
                            name="FPasswordno"
                            className="form-control"
                            value={values.FPasswordno}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">ID Number</label>
                        <input
                            type="text"
                            name="FPId"
                            className="form-control"
                            value={values.FPId}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            name="FPFirstName"
                            className="form-control"
                            value={values.FPFirstName}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            name="FPLastName"
                            className="form-control"
                            value={values.FPLastName}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Zip Code</label>
                        <input
                            type="text"
                            name="FPZipCode"
                            className="form-control"
                            value={values.FPZipCode}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Town</label>
                        <input
                            type="text"
                            name="FPtown"
                            className="form-control"
                            value={values.FPtown}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">House Number</label>
                        <input
                            type="text"
                            name="FPhouseNo"
                            className="form-control"
                            value={values.FPhouseNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Street</label>
                        <input
                            type="text"
                            name="FPstreet"
                            className="form-control"
                            value={values.FPstreet}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Expiry Date</label>
                        <input
                            type="date"
                            name="FPexpDate"
                            className="form-control"
                            value={values.FPexpDate}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Card Number</label>
                        <input
                            type="text"
                            name="FPcardNo"
                            className="form-control"
                            value={values.FPcardNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Cellphone</label>
                        <input
                            type="text"
                            name="FPcellphone"
                            className="form-control"
                            value={values.FPcellphone}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Email</label>
                        <input
                            type="text"
                            name="FPemail"
                            className="form-control"
                            value={values.FPemail}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            name="FPdate"
                            className="form-control"
                            value={values.FPdate}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Employer Name</label>
                        <input
                            type="text"
                            name="employername"
                            className="form-control"
                            value={values.employername}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            name="employerdate"
                            className="form-control"
                            value={values.employerdate}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Health Declaration
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            name="GFirstname"
                            className="form-control"
                            value={values.GFirstname}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            name="GLastname"
                            className="form-control"
                            value={values.GLastname}
                            onChange={handleChange}
                            readOnly
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Passport Number</label>
                        <input
                            type="text"
                            name="GPassportno"
                            className="form-control"
                            value={values.GPassportno}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Details</label>
                        <input
                            type="text"
                            name="GDetails"
                            className="form-control"
                            value={values.GDetails}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Candidate Name</label>
                        <input
                            type="text"
                            name="GCandidatename"
                            className="form-control"
                            value={values.GCandidatename}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            name="GDate"
                            className="form-control"
                            value={values.GDate}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Receipt of all the information in the policy
            </div>

            <div className="row justify-content-center">
                <div className="col-md-8">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="text"
                            name="Hname"
                            className="form-control"
                            value={values.Hname}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Signature for the Insurance Candidate
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Passport No.</label>
                        <input
                            type="text"
                            name="canPassportNo"
                            className="form-control"
                            value={values.canPassportNo}
                            onChange={handleChange}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Insurance Candidate Name
                        </label>
                        <input
                            type="text"
                            name="canName"
                            className="form-control"
                            value={values.canName}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-8">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            name="canDate"
                            className="form-control"
                            value={values.canDate}
                            onChange={handleChange}
                        />
                    </div>
                </div>
            </div>

            {/* Buttons */}
            <div>
                <div className="row justify-content-center">
                    <div className="col-md-8 d-flex">
                        <button
                            className="btn btn-secondary"
                            onClick={handleShow}
                            disabled={isSubmitting}
                        >
                            Preview
                        </button>
                        <div className="mx-2"></div>
                        {!isSubmitted && (
                            <button
                                className="btn btn-primary"
                                onClick={handleSubmit}
                                disabled={isSubmitting}
                            >
                                Submit
                            </button>
                        )}
                    </div>
                </div>

                <Modal
                    dialogClassName="pdf-dialog"
                    style={{
                        width: "auto",
                        maxWidth: "max-content !important",
                    }}
                    show={show}
                    onHide={handleClose}
                >
                    <Modal.Header closeButton>
                        <Modal.Title>Preview</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {!!pdfData && <PdfViewer url={pdfData} />}
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            Close
                        </Button>
                    </Modal.Footer>
                </Modal>
            </div>
        </div>
    );
};

function PdfViewer({ url }) {
    const [numPages, setNumPages] = useState();
    const [pageNumber, setPageNumber] = useState(1);

    function onDocumentLoadSuccess({ numPages }) {
        setNumPages(numPages);
    }

    return (
        <div>
            <Document file={url} onLoadSuccess={onDocumentLoadSuccess}>
                <Page pageNumber={pageNumber} />
            </Document>
            <div className="d-flex justify-content-center my-2 align-items-center">
                <button
                    className="btn btn-primary"
                    type="button"
                    disabled={pageNumber <= 1}
                    onClick={() => setPageNumber(pageNumber - 1)}
                >
                    Previous
                </button>
                <div className="mx-2">
                    Page {pageNumber} of {numPages}
                </div>
                <button
                    className="btn btn-primary"
                    type="button"
                    disabled={pageNumber >= numPages}
                    onClick={() => setPageNumber(pageNumber + 1)}
                >
                    Next
                </button>
            </div>
        </div>
    );
}

export default InsuranceForm;
