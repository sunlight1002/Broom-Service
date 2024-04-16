import React, { useState, useEffect } from "react";
import { PDFDocument } from "pdf-lib";
import TestPdf from "./testPdf.pdf";
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

    // page 1
    const [type, setType] = useState("New");
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
    const [canFirstName, setCanFirstName] = useState("");
    const [canLastName, setCanLastName] = useState("");
    const [canPassport, setCanPassport] = useState("");
    const [canOrigin, setCanOrigin] = useState("");
    const [canDOB, setCanDOB] = useState("");
    const [canFirstDateOfIns, setCanFirstDateOfIns] = useState("");
    const [canZipcode, setCanZipcode] = useState("");
    const [canTown, setCanTown] = useState("");
    const [canHouseNo, setCanHouseNo] = useState("");
    const [canStreet, setCanStreet] = useState("");
    const [canTelephone, setCanTelephone] = useState("");
    const [canCellPhone, setCanCellPhone] = useState("");
    const [canEmail, setCanEmail] = useState("");
    const [periodTo, setPeriodTo] = useState("");
    const [periodFrom, setPeriodFrom] = useState("");
    const [prevTo, setPrevTo] = useState("");
    const [prevFrom, setPrevFrom] = useState("");
    const [prevCompanyname, setPrevCompanyname] = useState("");
    const [prevPolicy, setPrevPolicy] = useState("");
    const [prevMemberShip, setPrevMemberShip] = useState("");
    const [prevInsurance, setPrevInsurance] = useState("Yes");
    const [occupasion, setOccupasion] = useState("other");
    const [gender, setGender] = useState("Male");

    // page 2
    const [FFirstName, setFFirstName] = useState("");
    const [FLastName, setFLastName] = useState("");
    const [FPasswordno, setFPasswordno] = useState("");
    const [FPId, setFPId] = useState("");
    const [FPFirstName, setFPFirstName] = useState("");
    const [FPLastName, setFPLastName] = useState("");
    const [FPZipCode, setFPZipCode] = useState("");
    const [FPtown, setFPtown] = useState("");
    const [FPhouseNo, setFPhouseNo] = useState("");
    const [FPstreet, setFPstreet] = useState("");
    const [FPexpDate, setFPexpDate] = useState("");
    const [FPcardNo, setFPcardNo] = useState("");
    const [FPcellphone, setFPcellphone] = useState("");
    const [FPemail, setFPemail] = useState("");
    const [FPdate, setFPdate] = useState("");
    const [employerdate, setEmployerdate] = useState("");
    const [employername, setEmployername] = useState("");
    const [Months, setMonths] = useState("6Months");
    const [sixMonthPayment, setSixMonthPayment] = useState("");
    const [twelveMonthsPayment, setTwelveMonthsPayment] = useState("");

    // page 3
    const [GFirstname, setGFirstname] = useState("");
    const [GLastname, setGLastname] = useState("");
    const [GPassportno, setGPassportno] = useState("");
    const [GDetails, setGDetails] = useState("");
    const [GCandidatename, setGCandidatename] = useState("");
    const [GDate, setGDate] = useState("");

    // page 4
    const [Hname, setHname] = useState("");
    const [canPassportNo, setCanPassportNo] = useState("");
    const [canName, setCanName] = useState("");
    const [canDate, setCanDate] = useState("");

    useEffect(() => {
        const fetchPdf = async () => {
            const formPdfBytes = await fetch(TestPdf).then((res) =>
                res.arrayBuffer()
            );
            const PdfDoc = await PDFDocument.load(formPdfBytes);
            setPdfDoc(PdfDoc);
            const form = PdfDoc.getForm();
            setPdfForm(form);
            const allFields = form.getFields();
            for (let index = 0; index < allFields.length; index++) {
                const element = allFields[index];
                console.log(element.getName());
            }
        };
        fetchPdf();
    }, []);

    const saveFormData = async () => {
        pdfForm.getRadioGroup("Type").select(type);
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
        pdfForm.getTextField("canFirstName").setText(canFirstName);
        pdfForm.getTextField("canLastName").setText(canLastName);
        pdfForm.getTextField("canPassport").setText(canPassport);
        pdfForm.getTextField("canOrigin").setText(canOrigin);
        pdfForm.getTextField("canDOB").setText(canDOB);
        pdfForm.getTextField("canFirstDateOfIns").setText(canFirstDateOfIns);
        pdfForm.getTextField("canZipcode").setText(canZipcode);
        pdfForm.getTextField("canTown").setText(canTown);
        pdfForm.getTextField("canHouseNo").setText(canHouseNo);
        pdfForm.getTextField("canStreet").setText(canStreet);
        pdfForm.getTextField("canTelephone").setText(canTelephone);
        pdfForm.getTextField("canCellPhone").setText(canCellPhone);
        pdfForm.getTextField("canEmail").setText(canEmail);
        pdfForm.getTextField("periodTo").setText(periodTo);
        pdfForm.getTextField("periodFrom").setText(periodFrom);
        pdfForm.getTextField("prevTo").setText(prevTo);
        pdfForm.getTextField("prevFrom").setText(prevFrom);
        pdfForm.getTextField("prevCompanyname").setText(prevCompanyname);
        pdfForm.getTextField("prevPolicy").setText(prevPolicy);
        pdfForm.getTextField("prevMemberShip").setText(prevMemberShip);
        pdfForm.getRadioGroup("prevInsurance").select(prevInsurance);
        pdfForm.getRadioGroup("occupasion").select(occupasion);
        pdfForm.getRadioGroup("gender").select(gender);

        pdfForm.getRadioGroup("Months").select(Months);
        pdfForm.getTextField("sixMonthPayment").setText(sixMonthPayment);
        pdfForm
            .getTextField("twelveMonthsPayment")
            .setText(twelveMonthsPayment);
        pdfForm.getTextField("F-FirstName").setText(FFirstName);
        pdfForm.getTextField("F-LastName").setText(FLastName);
        pdfForm.getTextField("F-Passwordno").setText(FPasswordno);
        pdfForm.getTextField("F-P-Id").setText(FPId);
        pdfForm.getTextField("F-P-FirstName").setText(FPFirstName);
        pdfForm.getTextField("F-P-LastName").setText(FPLastName);
        pdfForm.getTextField("F-P-ZipCode").setText(FPZipCode);
        pdfForm.getTextField("F-P-town").setText(FPtown);
        pdfForm.getTextField("F-P-houseNo").setText(FPhouseNo);
        pdfForm.getTextField("F-P-street").setText(FPstreet);
        pdfForm.getTextField("F-P-expDate").setText(FPexpDate);
        pdfForm.getTextField("F-P-cardNo").setText(FPcardNo);
        pdfForm.getTextField("F-P-cellphone").setText(FPcellphone);
        pdfForm.getTextField("F-P-email").setText(FPemail);
        pdfForm.getTextField("F-P-date").setText(FPdate);
        pdfForm.getTextField("employer-name").setText(employername);
        pdfForm.getTextField("employer-date").setText(employerdate);

        pdfForm.getTextField("G-firstname").setText(GFirstname);
        pdfForm.getTextField("G-lastname").setText(GLastname);
        pdfForm.getTextField("G-passportno").setText(GPassportno);
        pdfForm.getTextField("G-details").setText(GDetails);
        pdfForm.getTextField("G-candidatename").setText(GCandidatename);
        pdfForm.getTextField("G-date").setText(GDate);
        pdfForm.getTextField("H-name").setText(Hname);
        pdfForm.getTextField("candidate-passport-no").setText(canPassportNo);
        pdfForm.getTextField("candidate-name").setText(canName);
        pdfForm.getTextField("candidate-date").setText(canDate);

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
                        checked={type === "New"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setType("New")}
                    />
                    <Form.Check
                        label="reneaewal/extention"
                        name="group1"
                        checked={type === "Renewal"}
                        type="radio"
                        onClick={() => setType("Renewal")}
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
                            className="form-control"
                            value={canFirstName}
                            onChange={(e) => setCanFirstName(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={canLastName}
                            onChange={(e) => setCanLastName(e.target.value)}
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
                            className="form-control"
                            value={canPassport}
                            onChange={(e) => setCanPassport(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Origin</label>
                        <input
                            type="text"
                            className="form-control"
                            value={canOrigin}
                            onChange={(e) => setCanOrigin(e.target.value)}
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
                            className="form-control"
                            value={canDOB}
                            onChange={(e) => setCanDOB(e.target.value)}
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
                            className="form-control"
                            value={canFirstDateOfIns}
                            onChange={(e) =>
                                setCanFirstDateOfIns(e.target.value)
                            }
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
                            className="form-control"
                            value={canZipcode}
                            onChange={(e) => setCanZipcode(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Town</label>
                        <input
                            type="text"
                            className="form-control"
                            value={canTown}
                            onChange={(e) => setCanTown(e.target.value)}
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
                            className="form-control"
                            value={canHouseNo}
                            onChange={(e) => setCanHouseNo(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Street</label>
                        <input
                            type="text"
                            className="form-control"
                            value={canStreet}
                            onChange={(e) => setCanStreet(e.target.value)}
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
                            className="form-control"
                            value={canTelephone}
                            onChange={(e) => setCanTelephone(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Cellphone</label>
                        <input
                            type="text"
                            className="form-control"
                            value={canCellPhone}
                            onChange={(e) => setCanCellPhone(e.target.value)}
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
                            className="form-control"
                            value={canEmail}
                            onChange={(e) => setCanEmail(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <label className="control-label">Gender</label>
                    <Form.Check
                        label="Male"
                        name="Male"
                        checked={gender === "Male"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setGender("Male")}
                    />
                    <Form.Check
                        label="Female"
                        name="Female"
                        checked={gender === "Female"}
                        type="radio"
                        onClick={() => setGender("Female")}
                        id={`inline-2`}
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
                            className="form-control"
                            value={periodTo}
                            onChange={(e) => setPeriodTo(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Period From</label>
                        <input
                            type="text"
                            className="form-control"
                            value={periodFrom}
                            onChange={(e) => setPeriodFrom(e.target.value)}
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
                        name="nursing"
                        checked={occupasion === "nursing"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setOccupasion("nursing")}
                    />
                    <Form.Check
                        label="agriculture"
                        name="agriculture"
                        checked={occupasion === "agriculture"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setOccupasion("agriculture")}
                    />
                    <Form.Check
                        label="construction"
                        name="construction"
                        checked={occupasion === "construction"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setOccupasion("construction")}
                    />
                    <Form.Check
                        label="other"
                        name="other"
                        checked={occupasion === "other"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setOccupasion("other")}
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
                            className="form-control"
                            value={prevTo}
                            onChange={(e) => setPrevTo(e.target.value)}
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
                            className="form-control"
                            value={prevFrom}
                            onChange={(e) => setPrevFrom(e.target.value)}
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
                            className="form-control"
                            value={prevCompanyname}
                            onChange={(e) => setPrevCompanyname(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Policy Number</label>
                        <input
                            type="text"
                            className="form-control"
                            value={prevPolicy}
                            onChange={(e) => setPrevPolicy(e.target.value)}
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
                        name="Yes"
                        checked={prevInsurance === "Yes"}
                        type="radio"
                        id={`inline-1`}
                        onClick={() => setPrevInsurance("Yes")}
                    />
                    <Form.Check
                        label="No"
                        name="No"
                        checked={prevInsurance === "No"}
                        type="radio"
                        onClick={() => setPrevInsurance("No")}
                        id={`inline-2`}
                    />
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">
                            Membership Number
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={prevMemberShip}
                            onChange={(e) => setPrevMemberShip(e.target.value)}
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
                        name="installment"
                        checked={Months === "6Months"}
                        type="radio"
                        id={`inline-12`}
                        onClick={() => {
                            setMonths("6Months");
                            setTwelveMonthsPayment("");
                        }}
                    />
                </div>
                <div
                    className="col-md-4"
                    style={
                        Months === "12Months"
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
                            className="form-control"
                            value={sixMonthPayment}
                            onChange={(e) => setSixMonthPayment(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <Form.Check
                        label="12 Months"
                        name="installment"
                        checked={Months === "12Months"}
                        type="radio"
                        id={`inline-12`}
                        onClick={() => {
                            setMonths("12Months");
                            setSixMonthPayment("");
                        }}
                    />
                </div>
                <div
                    className="col-md-4"
                    style={
                        Months === "6Months"
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
                            className="form-control"
                            value={twelveMonthsPayment}
                            onChange={(e) =>
                                setTwelveMonthsPayment(e.target.value)
                            }
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
                            className="form-control"
                            value={FFirstName}
                            onChange={(e) => setFFirstName(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={FLastName}
                            onChange={(e) => setFLastName(e.target.value)}
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
                            className="form-control"
                            value={FPasswordno}
                            onChange={(e) => setFPasswordno(e.target.value)}
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
                            className="form-control"
                            value={FPId}
                            onChange={(e) => setFPId(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={FPFirstName}
                            onChange={(e) => setFPFirstName(e.target.value)}
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
                            value={FPLastName}
                            onChange={(e) => setFPLastName(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Zip Code</label>
                        <input
                            type="text"
                            className="form-control"
                            value={FPZipCode}
                            onChange={(e) => setFPZipCode(e.target.value)}
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
                            value={FPtown}
                            onChange={(e) => setFPtown(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">House Number</label>
                        <input
                            type="text"
                            className="form-control"
                            value={FPhouseNo}
                            onChange={(e) => setFPhouseNo(e.target.value)}
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
                            value={FPstreet}
                            onChange={(e) => setFPstreet(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Expiry Date</label>
                        <input
                            type="date"
                            className="form-control"
                            value={FPexpDate}
                            onChange={(e) => setFPexpDate(e.target.value)}
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
                            className="form-control"
                            value={FPcardNo}
                            onChange={(e) => setFPcardNo(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Cellphone</label>
                        <input
                            type="text"
                            className="form-control"
                            value={FPcellphone}
                            onChange={(e) => setFPcellphone(e.target.value)}
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
                            className="form-control"
                            value={FPemail}
                            onChange={(e) => setFPemail(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            className="form-control"
                            value={FPdate}
                            onChange={(e) => setFPdate(e.target.value)}
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
                            className="form-control"
                            value={employername}
                            onChange={(e) => setEmployername(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            className="form-control"
                            value={employerdate}
                            onChange={(e) => setEmployerdate(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Health Delclaration
            </div>

            <div className="row justify-content-center">
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">First Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={GFirstname}
                            onChange={(e) => setGFirstname(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Last Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={GLastname}
                            onChange={(e) => setGLastname(e.target.value)}
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
                            className="form-control"
                            value={GPassportno}
                            onChange={(e) => setGPassportno(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Details</label>
                        <input
                            type="text"
                            className="form-control"
                            value={GDetails}
                            onChange={(e) => setGDetails(e.target.value)}
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
                            className="form-control"
                            value={GCandidatename}
                            onChange={(e) => setGCandidatename(e.target.value)}
                        />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="date"
                            className="form-control"
                            value={GDate}
                            onChange={(e) => setGDate(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div
                className="row justify-content-center my-2"
                style={{ fontSize: "22px", fontWeight: "bold" }}
            >
                Reciept of all the information in the policy
            </div>

            <div className="row justify-content-center">
                <div className="col-md-8">
                    <div className="form-group">
                        <label className="control-label">Date</label>
                        <input
                            type="text"
                            className="form-control"
                            value={Hname}
                            onChange={(e) => setHname(e.target.value)}
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
                            className="form-control"
                            value={canPassportNo}
                            onChange={(e) => setCanPassportNo(e.target.value)}
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
                            className="form-control"
                            value={canName}
                            onChange={(e) => setCanName(e.target.value)}
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
                            className="form-control"
                            value={canDate}
                            onChange={(e) => setCanDate(e.target.value)}
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
