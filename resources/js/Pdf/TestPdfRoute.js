import React, { useState, useEffect } from "react";
import { PDFDocument } from "pdf-lib";
import TestPdf from "./test.pdf";
import { pdfjs } from "react-pdf";
import { Document, Page } from "react-pdf";
import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import Form from "react-bootstrap/Form";
import "react-pdf/dist/Page/AnnotationLayer.css";
import "react-pdf/dist/Page/TextLayer.css";

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
    "pdfjs-dist/build/pdf.worker.min.js",
    import.meta.url
).toString();

const TestPdfRoute = () => {
    const [show, setShow] = useState(false);

    const [pdfForm, setPdfForm] = useState(null);
    const [pdfDoc, setPdfDoc] = useState(null);
    const [pdfData, setPdfData] = useState(null);

    const [type, setType] = useState("");
    const [AgentName, setAgentName] = useState("");
    const [AgentNo, setAgentNo] = useState("");
    const [CompanyName, setCompanyName] = useState("");
    const [CompanyNo, setCompanyNo] = useState("");
    const [AgreementNo, setAgreementNo] = useState("");
    const [IDNumber, setIDNumber] = useState("");
    const [FirstName, setFirstName] = useState("");
    const [LastName, setLastName] = useState("");
    const [ZipCode, setZipCode] = useState("");
    const [Town, setTown] = useState("");
    const [HouseNumber, setHouseNumber] = useState("");
    const [Street, setStreet] = useState("");
    const [Email, setEmail] = useState("");
    const [CellphoneNo, setCellphoneNo] = useState("");
    const [TelephoneNo, setTelephoneNo] = useState("");

    useEffect(() => {
        const fetchPdf = async () => {
            const formPdfBytes = await fetch(TestPdf).then((res) =>
                res.arrayBuffer()
            );
            const PdfDoc = await PDFDocument.load(formPdfBytes);
            setPdfDoc(PdfDoc);
            const form = PdfDoc.getForm();
            setPdfForm(form);
            // const allFields = form.getFields();
            // for (let index = 0; index < allFields.length; index++) {
            //     const element = allFields[index];
            //     console.log(element.getName()); // [];
            // }
        };
        fetchPdf();
    }, []);

    const saveFormData = async () => {
        pdfForm.getCheckBox("Type").check(type);
        pdfForm.getTextField("AgentName").setText(AgentName);
        pdfForm.getTextField("AgentNo").setText(AgentNo);
        pdfForm.getTextField("CompanyName").setText(CompanyName);
        pdfForm.getTextField("CompanyNo").setText(CompanyNo);
        pdfForm.getTextField("AgreementNo").setText(AgreementNo);
        pdfForm.getTextField("IDNumber").setText(IDNumber);
        pdfForm.getTextField("FirstName").setText(FirstName);
        pdfForm.getTextField("LastName").setText(LastName);
        pdfForm.getTextField("ZipCode").setText(ZipCode);
        pdfForm.getTextField("Town").setText(Town);
        pdfForm.getTextField("HouseNumber").setText(HouseNumber);
        pdfForm.getTextField("Street").setText(Street);
        pdfForm.getTextField("Email").setText(Email);
        pdfForm.getTextField("CellphoneNo").setText(CellphoneNo);
        pdfForm.getTextField("TelephoneNo").setText(TelephoneNo);
        const pdfBytes = await pdfDoc.save();
        const blob = new Blob([pdfBytes], { type: "application/pdf" });
        const url = URL.createObjectURL(blob);
        setPdfData(url);
        console.log(pdfBytes, "arrayBytes");
        console.log(blob, "blob");
    };

    const handleSubmit = async () => {
        await saveFormData();
    };

    const handleShow = async () => {
        await saveFormData();
        setShow(true);
    };

    const handleClose = () => setShow(false);

    return (
        <div className="my-2">
            <div className="row justify-content-center">
                <div className="col-md-8">
                    <label className="control-label">Type</label>
                    <Form.Check
                        label="a new candidate"
                        name="group1"
                        checked={type === "a new candidate"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setType("a new candidate")}
                    />
                    <Form.Check
                        label="reneaewal/extention"
                        name="group1"
                        checked={type === "reneaewal/extention"}
                        type="radio"
                        onClick={() => setType("reneaewal/extention")}
                        id={`inline-2`}
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
                            className="form-control"
                            value={AgentName}
                            onChange={(e) => setAgentName(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Agent Number</label>
                        <input
                            type="text"
                            className="form-control"
                            value={AgentNo}
                            onChange={(e) => setAgentNo(e.target.value)}
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
                            className="form-control"
                            value={CompanyName}
                            onChange={(e) => setCompanyName(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">CompanyNo</label>
                        <input
                            type="text"
                            className="form-control"
                            value={CompanyNo}
                            onChange={(e) => setCompanyNo(e.target.value)}
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
                            className="form-control"
                            value={AgreementNo}
                            onChange={(e) => setAgreementNo(e.target.value)}
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
                            className="form-control"
                            value={IDNumber}
                            onChange={(e) => setIDNumber(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={FirstName}
                            onChange={(e) => setFirstName(e.target.value)}
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
                            className="form-control"
                            value={LastName}
                            onChange={(e) => setLastName(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Zip Code</label>
                        <input
                            type="text"
                            className="form-control"
                            value={ZipCode}
                            onChange={(e) => setZipCode(e.target.value)}
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
                            className="form-control"
                            value={Town}
                            onChange={(e) => setTown(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">House Number</label>
                        <input
                            type="text"
                            className="form-control"
                            value={HouseNumber}
                            onChange={(e) => setHouseNumber(e.target.value)}
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
                            className="form-control"
                            value={Street}
                            onChange={(e) => setStreet(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Email</label>
                        <input
                            type="text"
                            className="form-control"
                            value={Email}
                            onChange={(e) => setEmail(e.target.value)}
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
                            className="form-control"
                            value={CellphoneNo}
                            onChange={(e) => setCellphoneNo(e.target.value)}
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
                            className="form-control"
                            value={TelephoneNo}
                            onChange={(e) => setTelephoneNo(e.target.value)}
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
                        >
                            Preview
                        </button>
                        <div className="mx-2"></div>
                        <button
                            className="btn btn-primary"
                            onClick={handleSubmit}
                        >
                            Submit
                        </button>
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

export default TestPdfRoute;
